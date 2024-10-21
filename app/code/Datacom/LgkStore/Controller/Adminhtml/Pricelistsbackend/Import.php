<?php

namespace Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend;

class Import extends \Magento\Backend\App\Action {
    
    protected $_uploaderFactory;
    protected $_adapterFactory;
    protected $_filesystem;
    protected $_productRepository;
    protected $_dtmPriceListHelper;
    protected $_date;
    protected $_jsonHelper;

    public function __construct(
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Image\AdapterFactory $adapterFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Datacom\LgkStore\Helper\PriceLists $dtmPriceListHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
        
        $this->_uploaderFactory = $uploaderFactory;
        $this->_adapterFactory = $adapterFactory;
        $this->_filesystem = $filesystem;
        $this->_productRepository = $productRepository;
        $this->_dtmPriceListHelper = $dtmPriceListHelper;
        $this->_date = $date;
        $this->_jsonHelper = $jsonHelper;
    } 

    public function execute()
    {
        try {
            $uploaderFactory = $this->_uploaderFactory->create(['fileId' => 'price_list']); 
            $uploaderFactory->setAllowedExtensions(['csv']);
            $fileAdapter = $this->_adapterFactory->create();
            $uploaderFactory->setAllowRenameFiles(true);
            $uploaderFactory->setFilesDispersion(false);
            $varDirectory = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            $destinationPath = $varDirectory->getAbsolutePath('import/pricelists');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath);
            }
            $result = $uploaderFactory->save($destinationPath);
            //print_r($result)
            if (!$result) {
                throw new LocalizedException(
                    __('File cannot be saved to path: $1', $destinationPath)
                );
            }
            
        } catch (\Exception $ex) {
            $this->messageManager->addError($ex->getMessage());
            return;
        }

        $savedFile = sprintf('%s/%s', $result['path'], $result['file']);
        
        $i = 0;
        do {
            $i++;
            $newFilename = sprintf('%s.%d.lock', $result['file'], $i);
            $newFile = sprintf('%s/%s', $result['path'], $newFilename);
        } while (file_exists($newFile));

        rename($savedFile, $newFile);

        $productsToDo = $this->getFileContent($newFile);

        $nowStr = $this->_date->date()->format('YmdHis');

        $completedFilename = sprintf('%s_%s', $nowStr, $result['file']);
        $completedFile = sprintf('%s/completed/%s', $result['path'], $completedFilename);

        $hasUpdated = $this->updateProducts($productsToDo, sprintf('%s/backup/', $result['path']), $nowStr, $completedFile);

        if ($hasUpdated) {
            if (file_exists($completedFile)) {
                unlink($completedFile);
            }

            rename($newFile, $completedFile);

            $this->messageManager->addSuccess(__('Price list updated')); 
        } else {
            $this->messageManager->addSuccess(__('There was no need to update any price')); 
            unlink($newFile);
        }

        return $this->resultRedirectFactory->create()->setPath(
            'lgkstore/pricelistsbackend/index', ['_secure'=>$this->getRequest()->isSecure()]
        ); 

        /*$uploadedPlatesFile = $this->request->getFiles('groups')['general']['fields']['upload_plates_file']['value'];
        //$this->getRequest()->getFiles()
        
        $rootDir = $this->_fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $uploadDir = $this->_writeFactory->create($rootDir->getAbsolutePath('directoryname'));
        $realpath = $uploadDir->getDriver()->getRealPathSafety($uploadDir->getAbsolutePath('file'));
        $uploadDir->getDriver()->copy(
            $uploadedPlatesFile['tmp_name'],
            $realpath
        );

        return $this;*/
    }

    protected function _isAllowed()
    {
        //return true;
        return $this->_authorization->isAllowed('Datacom_LgkStore::menu');
    }

    private function updateProducts($productsData, $backupFolderRoot, $nowStr, $completedFile) {
        $hasUpdated = false;

        foreach ($productsData as $sku => $pd) {
            try {
                $targetProduct = $this->_productRepository->get($sku);

                if (!$targetProduct || !$targetProduct->getId()) throw new NoSuchEntityException();
            } catch (NoSuchEntityException $ex) {
                continue;
            }

            //die(print_r($pd, true));

            if (!$this->_dtmPriceListHelper->mustChangeProduct(
                $targetProduct,
                //$pd['codice_fornitore'],
                $pd['prezzo_di_acquisto'],
                $pd['price'],
                $pd['special_price'],
                $pd['qta_azienda_italiana'],
                $pd['prezzo_azienda_italiana'],
                $pd['qta_azienda_estera'],
                $pd['prezzo_azienda_estera'],
                $pd['qta_privato'],
                $pd['prezzo_privato'],
                $pd['qta_privato_cee'],
                $pd['prezzo_privato_cee'],
                $pd['qta_privato_extra_cee'],
                $pd['prezzo_privato_extra_cee'],
                $pd['qta_rivenditori_esteri'],
                $pd['prezzo_rivenditori_esteri'],
                $pd['qta_rivenditori_italiani'],
                $pd['prezzo_rivenditori_italiani'])) continue;

            $productBackup = $this->_dtmPriceListHelper->getProductBackupData($targetProduct);
            $i = 0;
            do {
                $i++;
                $productBackupFilename = sprintf('%s.%d.json', $nowStr, $i);
                $productBackupFile = sprintf('%s/%d/%s', $backupFolderRoot, $targetProduct->getId(), $productBackupFilename);
            } while (file_exists($productBackupFile));

            $productBackupFileFolder = dirname($productBackupFile);

            if (!is_dir($productBackupFileFolder)) {
                mkdir($productBackupFileFolder, 0755);
            }

            file_put_contents($productBackupFile, $this->_jsonHelper->jsonEncode($productBackup));

            if (!file_exists($completedFile)) {
                touch($completedFile);
            }

            $hasUpdated = true;

            $this->_dtmPriceListHelper->updateProductData($targetProduct, $pd['prezzo_di_acquisto'], $pd['price'], $pd['special_price']);

            $this->_dtmPriceListHelper->updateProductPrices($sku, 
                $pd['qta_azienda_italiana'], $pd['prezzo_azienda_italiana'], 
                $pd['qta_azienda_estera'], $pd['prezzo_azienda_estera'], 
                $pd['qta_privato'], $pd['prezzo_privato'], 
                $pd['qta_privato_cee'], $pd['prezzo_privato_cee'], 
                $pd['qta_privato_extra_cee'], $pd['prezzo_privato_extra_cee'], 
                $pd['qta_rivenditori_esteri'], $pd['prezzo_rivenditori_esteri'], 
                $pd['qta_rivenditori_italiani'], $pd['prezzo_rivenditori_italiani']);
        }

        return $hasUpdated;
    }

    private function getFileContent($file) {
        $firstRow = true;
        $rows = file($file);
        $retval = [];
        foreach ($rows as $r) {
            if ($firstRow) {
                $firstRow = false;
                continue;
            }

            $row = trim($r);
            if (empty($row)) continue;
            if (str_contains($row, '"')) {
                $row = trim($row, '"');
                if (empty($row)) continue;
                $row = explode('","', $row);
            } else {
                $row = explode(';', $row);
            }

            if (count($row) != 19) throw new \Exception('Numero elementi per riga errato');

            $retval[$row[0]] = [
                //'codice_fornitore' => $row[1],
                'prezzo_di_acquisto' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[2])),
                'price' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[3])),
                'special_price' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[4])),
                'qta_azienda_italiana' => intval($row[5]),
                'prezzo_azienda_italiana' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[6])),
                'qta_azienda_estera' => intval($row[7]),
                'prezzo_azienda_estera' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[8])),
                'qta_privato' => intval($row[9]),
                'prezzo_privato' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[10])),
                'qta_privato_cee' => intval($row[11]),
                'prezzo_privato_cee' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[12])),
                'qta_privato_extra_cee' => intval($row[13]),
                'prezzo_privato_extra_cee' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[14])),
                'qta_rivenditori_esteri' => intval($row[15]),
                'prezzo_rivenditori_esteri' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[16])),
                'qta_rivenditori_italiani' => intval($row[17]),
                'prezzo_rivenditori_italiani' => floatval(str_replace(\Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, '.', $row[18]))
            ];
        }
        return $retval;
    }
}
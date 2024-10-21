<?php

namespace Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend;

class BackupDownload extends \Magento\Backend\App\Action {
    
    protected $_fileFactory;
    protected $_date;
    protected $_dtmPriceListsHelper;
    protected $_dirList;
    protected $_jsonHelper;
    protected $_driverFile;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Datacom\LgkStore\Helper\PriceLists $dtmPriceListsHelper,
        \Magento\Framework\Filesystem\DirectoryList $dirList,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Filesystem\Driver\File $driverFile
    ) {
        parent::__construct($context);

        $this->_fileFactory = $fileFactory;
        $this->_date = $date;
        $this->_dtmPriceListsHelper = $dtmPriceListsHelper;
        $this->_dirList = $dirList;
        $this->_jsonHelper = $jsonHelper;
        $this->_driverFile = $driverFile;
    } 

    public function execute()
    {
        $requestedDatetime = $this->getRequest()->getParam('requested_datetime');

        if (!$requestedDatetime) {
            $this->messageManager->addError('Manca il parametro di specifica per data e orario');
            return $this->resultRedirectFactory->create()->setPath(
                'lgkstore/pricelistsbackend/backuplisting', ['_secure'=>$this->getRequest()->isSecure()]
            ); 
        }

        $now = $this->_date->date();
        $nowStr = $now->format('YmdHis');

        $varFilepath = sprintf('export/backup.%s.csv', $nowStr);
        $realFilepath = sprintf('%s/%s', $this->_dirList->getPath('var'), $varFilepath);

        if (file_exists($realFilepath)) {
            unlink($realFilepath);
        }

        $vals = $this->_dtmPriceListsHelper->getCsvHeader();
        $row = sprintf('"%s"', implode('","', $vals));
        file_put_contents($realFilepath, $row."\r\n", FILE_APPEND);

        $products = $this->getProductsBackup($requestedDatetime, sprintf('%s/import/pricelists/backup', $this->_dirList->getPath('var')));
        foreach ($products as $prod) {
            $vals = [
                $prod['sku'],
                $prod['codice_fornitore'],
                number_format($prod['prezzo_di_acquisto'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                number_format($prod['price'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                number_format($prod['special_price'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                intval($prod['qta_azienda_italiana']),
                number_format($prod['prezzo_azienda_italiana'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                intval($prod['qta_azienda_estera']),
                number_format($prod['prezzo_azienda_estera'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                intval($prod['qta_rivenditori_italiani']),
                number_format($prod['prezzo_rivenditori_italiani'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                intval($prod['qta_rivenditori_esteri']),
                number_format($prod['prezzo_rivenditori_esteri'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                intval($prod['qta_privato']),
                number_format($prod['prezzo_privato'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                intval($prod['qta_privato_cee']),
                number_format($prod['prezzo_privato_cee'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
                intval($prod['qta_privato_extra_cee']),
                number_format($prod['prezzo_privato_extra_cee'], 2, \Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend\Export::CSV_DECIMAL_SEPARATOR, ''),
            ];
            $row = sprintf('"%s"', implode('","', $vals));
            file_put_contents($realFilepath, $row."\r\n", FILE_APPEND);
        }

        if (empty($products)) {
            if (file_exists($realFilepath)) {
                unlink($realFilepath);
            }
            $this->messageManager->addError('Non ci sono backup per la data e l\'orario selezionati');
            return $this->resultRedirectFactory->create()->setPath(
                'lgkstore/pricelistsbackend/backuplisting', ['_secure'=>$this->getRequest()->isSecure()]
            ); 
        }

        return $this->_fileFactory->create(
            sprintf('Backup listini %s.csv', $now->format('Y-m-d H-i-s')),
            [
                'type' => 'filename',
                'value' => $varFilepath,
                'rm' => 1
            ],
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }

    protected function _isAllowed()
    {
        //return true;
        return $this->_authorization->isAllowed('Datacom_LgkStore::menu');
    }

    private function getProductsBackup($datetime, $backupPath) {
        $retval = [];
        $files = $this->_driverFile->readDirectoryRecursively($backupPath);
        foreach ($files as $f) {
            $filename = basename($f);
            if (substr($filename, 0, 14) != $datetime) continue;
            $retval[] = $this->_jsonHelper->jsonDecode(file_get_contents($f));
        }
        return $retval;
    }
}
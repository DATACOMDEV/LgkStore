<?php

namespace Datacom\LgkStore\Console\Command;

class GenerateCustomerPricePerCategoryCommand extends \Symfony\Component\Console\Command\Command {
    
    const PRICE_CSV_FOLDER = 'price_csv';
    const PRICE_ORIGINAL_FILE = 'products_prices.csv';
    const PRICE_HANDLED_FILE = 'csv_to_upload.csv';
    const ERROR_LOG_FILE = 'error.log';

    protected $_csv;
    protected $_categoryRepository;
    protected $_filesystem;
    protected $_conn;
    protected $_customerFactory;
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\File\Csv $csv,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Model\CustomerFactory $customerFactory
	) {
        parent::__construct();
        
        $this->_state = $state;
        $this->_csv = $csv;
        $this->_categoryRepository = $categoryRepository;
        $this->_filesystem = $filesystem;
        $this->_conn = $resourceConnection;
        $this->_customerFactory = $customerFactory;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
    }

    protected function configure() {
        $this->setName('datacom:generatecustomerpricepercategorycommand')->setDescription('Legge il csv caricato e valorizza i prezzi per categoria per cliente');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $this->_state->setAreaCode('adminhtml');

        $lockFile = dirname(__FILE__).'/GenerateCustomerPricePerCategoryCommand.lock';

        if (file_exists($lockFile)) return;

        touch($lockFile);

        $err = null;

        try {
            $this->_execute($input, $output);
        } catch (\Exception $ex) {
            $err = $ex;
        }

        if (file_exists($lockFile)) {
            unlink($lockFile);
        }

        if (!is_null($err)) throw $err;
    }

    protected function _execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $now = new \DateTime();
        $oldCsvFile = BP.'/'.self::PRICE_CSV_FOLDER.'/'.self::PRICE_ORIGINAL_FILE;
        $baseNowFile = dirname($oldCsvFile).'/'.$now->format('YmdHis').'.';

        try {
            $newCsvFile = $this->__convertCsvFile($oldCsvFile);

            if (empty($newCsvFile)) return;

            rename($oldCsvFile, $baseNowFile.basename($oldCsvFile));
            $this->__uploadAndImport($newCsvFile);
            rename($newCsvFile, $baseNowFile.basename($newCsvFile));
        } catch (\Exception $ex) {
            $errFile = $baseNowFile.self::ERROR_LOG_FILE;
            file_put_contents($errFile, $ex->getMessage()."\r\n", FILE_APPEND);
            file_put_contents($errFile, $ex->getTraceAsString()."\r\n", FILE_APPEND);
            throw $ex;
        }
    }

    protected function __convertCsvFile($filePath) {
        $newCsvFilePath = dirname($filePath).'/'.self::PRICE_HANDLED_FILE;

        if (!file_exists($filePath)) return null;

        if (file_exists($newCsvFilePath)) {
            unlink($newCsvFilePath);
        }

        file_put_contents($newCsvFilePath, "customer_email,category_id,discount,website\r\n", FILE_APPEND);

        $csvData = $this->_csv->getData($filePath);
        foreach ($csvData as $row => $data) {
            $customerMail = $data[0];
            $categoriesId = $data[1];

            if (empty($customerMail)) continue;

            if (empty($categoriesId)) continue;

            if (!is_numeric($data[2])) continue;

            $discountPercent = intval($data[2]);

            $allCategoriesIds = [];
            $categoriesId = explode('_', $categoriesId);
            foreach ($categoriesId as $catId) {
                try {
                    $curCat = $this->_categoryRepository->get($catId);

                    if (!$curCat || !$curCat->getId()) throw new \Magento\Framework\Exception\NoSuchEntityException();
                } catch (\Magento\Framework\Exception\NoSuchEntityException $ex) {
                    continue;
                }

                if (!array_key_exists($curCat->getId(), $allCategoriesIds)) {
                    $allCategoriesIds[$curCat->getId()] = 1;
                    file_put_contents($newCsvFilePath, sprintf("%s,%d,%d,1\r\n", $customerMail, $curCat->getId(), $discountPercent), FILE_APPEND);
                }

                $childrenCategories = $curCat->getChildrenCategories();
                foreach ($childrenCategories as $childCat) {
                    if (array_key_exists($childCat->getId(), $allCategoriesIds)) continue;

                    $allCategoriesIds[$childCat->getId()] = 1;
                    file_put_contents($newCsvFilePath, sprintf("%s,%d,%d,1\r\n", $customerMail, $childCat->getId(), $discountPercent), FILE_APPEND);
                }
            }
        }

        return $newCsvFilePath;
    }

    protected function __uploadAndImport($csvFile) {
        try {
            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        } catch (\Exception $e) {
            if ($e->getCode() == '666') {
                return $this;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }
        
        $website = $this->_storeManager->getWebsite(1);
        
        $this->_importWebsiteId = (int) $website->getId();
        $this->_importUniqueHash = [];
        $this->_importErrors = [];
        $this->_importedRows = 0;

        /*$tmpDirectory = $this->_filesystem->getDirectoryRead(\Magento\Framework\Filesystem\DirectoryList::SYS_TMP);
        $path = $tmpDirectory->getRelativePath($csvFile);
        $stream = $tmpDirectory->openFile($path);*/
        $csvData = $this->_csv->getData($csvFile);

        // check and skip headers
        /*$headers = $stream->readCsv();
        if ($headers === false || count($headers) < 1) {
            $stream->close();
            throw new \Magento\Framework\Exception\LocalizedException(__('Please correct Price Per Customer File Format.'));
        }*/

        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $rowNumber = 1;
            $importData = [];

            foreach ($csvData as $row => $data) {
                if ($row == 0) {
                    $headers = $data;
                    continue;
                }
                ++$rowNumber;
                $csvLine = $data;
                $row = $this->___getImportRow($csvLine, $rowNumber, $headers);
                //echo "<pre>"; print_r($row);
                if ($row !== false && $row['category_id'] > 0 && $row['customer_id'] > 0) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $priceCollection = $objectManager->create('Magedelight\Customerprice\Model\ResourceModel\Categoryprice\CollectionFactory')->create();
                    $priceCollection->addFieldToFilter('category_id', $row['category_id']);
                    $priceCollection->addFieldToFilter('customer_id', $row['customer_id']);
                    $priceCustomer = null;
                    switch (count($priceCollection)) {
                        case 0:
                            $priceCustomer = $objectManager->create('Magedelight\Customerprice\Model\Categoryprice');
                            $priceCustomer->setData($row);
                            break;
                        case 1:
                            foreach ($priceCollection as $pc) {
                                $priceCustomer = $pc;

                                if ($priceCustomer->getDiscount() == $row['discount']) continue;

                                $priceCustomer->setDiscount($row['discount']);
                            }
                            break;
                        default:
                            throw new \Exception('Esistono molteplici record categoria-cliente');
                    }

                    if (is_null($priceCustomer)) continue;

                    if ($priceCustomer->getDiscount() == 0) {
                        $priceCustomer->delete();
                        continue;
                    }

                    $priceCustomer->save();
                }
            }
            //exit();
            //$stream->close();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $connection->rollback();
            //$stream->close();
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $connection->rollback();
            //$stream->close();
            //$this->_logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while importing prices.')
            );
        }

        $connection->commit();

        if ($this->_importErrors) {
            $error = __(
                'We couldn\'t import this file because of these errors: %1',
                implode(" \n", $this->_importErrors)
            );
            throw new \Magento\Framework\Exception\LocalizedException($error);
        }

        return $this;
    }

    protected function ___getImportRow($row, $rowNumber = 0, $headers)
    {
        if (count($row) < 3) {
            //echo "hiii";
            
            $this->_importErrors[] = __('Please correct Table Rates format in the Row #%1.', $rowNumber);
            
            return false;
        }
        $emailKey = array_search('customer_email', $headers);
        $catKey = array_search('category_id', $headers);
        $discountKey = array_search('discount', $headers);
        $websiteKey = array_search('website', $headers);
        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        $email = $row[$emailKey];
        $cat = $row[$catKey];
        if ($websiteKey) {
            $website_id = $row[$websiteKey];
        }
        $discount = $row[$discountKey];
        $matches = [];
        if (!is_numeric($discount)) {
            preg_match('/(.*)%/', $newprice, $matches);
            if ((is_array($matches) && count($matches) <= 0) || !is_numeric($matches[1])) {
                $this->_importErrors[] = __('Invalid discount "%1" in the Row #%2.', $row[$discountKey], $rowNumber);

                return false;
            } elseif (is_numeric($matches[1]) && ($matches[1] <= 0 || $matches[1] > 100)) {
                $this->_importErrors[] = __('Invalid New Price "%1" in the Row #%2.Percentage should be greater than 0 and less or equals than 100.', $row[$discount], $rowNumber);

                return false;
            }
        }

        if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $email)) {
            $this->_importErrors[] = __('Invalid email "%1" in the Row #%2.', $row[$emailKey], $rowNumber);

            return false;
        }

        if ($websiteKey) {
            $customer = $this->_customerFactory->create()->getCollection()
                    ->addNameToSelect()
                    ->addAttributeToSelect('entity_id')
                    ->addAttributeToSelect('email')
                    ->addAttributeToSelect('group_id')
                    ->addFieldToFilter('email', $email)
                    ->addFieldToFilter('website_id', $website_id)
                    ->getFirstItem();
        } else {
            $customer = $this->_customerFactory->create()->getCollection()
                    ->addNameToSelect()
                    ->addAttributeToSelect('entity_id')
                    ->addAttributeToSelect('email')
                    ->addAttributeToSelect('group_id')
                    ->addFieldToFilter('email', $email)
                    ->getFirstItem();
        }
        
        $customerId = $customer->getId();
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //$this->getCategoryChild($cat);
        $category = $objectManager->get('Magento\Catalog\Model\Category')->load($cat);

        $categoryName = $category->getName();
       
        return [
            'customer_id' => $customerId, // Customer Id
            'customer_name' => $customer->getName(), // Customer Name
            'customer_email' => $email, // customer email
            'category_id' => $category->getId(), // Category Id
            'category_name' => $categoryName, // Category Name
            'discount' => $discount, //discont
        ];
    }

    public function getConnection()
    {
        return $this->_conn->getConnection();
    }
}
<?php

namespace Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend;

class Export extends \Magento\Backend\App\Action {
    
    const CSV_DECIMAL_SEPARATOR = ',';

    protected $_fileFactory;
    protected $_dtmPriceListsHelper;
    protected $_dirList;    

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Datacom\LgkStore\Helper\PriceLists $dtmPriceListsHelper,
        \Magento\Framework\Filesystem\DirectoryList $dirList
    ) {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
        $this->_dtmPriceListsHelper = $dtmPriceListsHelper;
        $this->_dirList = $dirList;
    } 

    public function execute()
    {
        //$filepath = sprintf('%s/productslist.csv', $this->_dirList->getPath('pub'));
        $varFilepath = 'export/productslist.csv';
        $realFilepath = sprintf('%s/%s', $this->_dirList->getPath('var'), $varFilepath);

        $manufacturerId = $this->getRequest()->getParam('manufacturer_id');
        if (empty($manufacturerId)) {
            $manufacturerId = 0;
        }

        if (file_exists($realFilepath)) {
            unlink($realFilepath);
        }

        $vals = $this->_dtmPriceListsHelper->getCsvHeader();
        $row = sprintf('"%s"', implode('","', $vals));
        file_put_contents($realFilepath, $row."\r\n", FILE_APPEND);

        $products = $this->_dtmPriceListsHelper->getProducts($manufacturerId);
        
        foreach ($products as $prod) {
            $tierData = $this->_dtmPriceListsHelper->getProductTierPrices($prod->getSku());
            $vals = [
                $prod->getSku(),
                $prod->getData('codice_fornitore'),
                number_format(round($prod->getData('prezzo_di_acquisto'), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                number_format(round($prod->getPrice(), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                number_format(round($prod->getSpecialPrice(), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                intval($this->getTierPriceField($tierData, 'azienda italiana', 'qty')),
                number_format(round($this->getTierPriceField($tierData, 'azienda italiana', 'price'), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                intval($this->getTierPriceField($tierData, 'azienda estera', 'qty')),
                number_format(round($this->getTierPriceField($tierData, 'azienda estera', 'price'), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                intval($this->getTierPriceField($tierData, 'privato', 'qty')),
                number_format(round($this->getTierPriceField($tierData, 'privato', 'price'), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                intval($this->getTierPriceField($tierData, 'privato cee', 'qty')),
                number_format(round($this->getTierPriceField($tierData, 'privato cee', 'price'), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                intval($this->getTierPriceField($tierData, 'privato extra cee', 'qty')),
                number_format(round($this->getTierPriceField($tierData, 'privato extra cee', 'price'), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                intval($this->getTierPriceField($tierData, 'rivenditori esteri', 'qty')),
                number_format(round($this->getTierPriceField($tierData, 'rivenditori esteri', 'price'), 2), 2, self::CSV_DECIMAL_SEPARATOR, ''),
                intval($this->getTierPriceField($tierData, 'rivenditori italiani', 'qty')),
                number_format(round($this->getTierPriceField($tierData, 'rivenditori italiani', 'price'), 2), 2, self::CSV_DECIMAL_SEPARATOR, '')
            ];
            $row = sprintf('"%s"', implode('","', $vals));
            file_put_contents($realFilepath, $row."\r\n", FILE_APPEND);
        }
        return $this->_fileFactory->create(
            'ProductsList.csv', 
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

    private function getTierPriceField($tierData, $group, $field) {
        if (!array_key_exists($group, $tierData)) throw new \Exception(sprintf('Missing customer group: %s', $group));
        if (!array_key_exists($field, $tierData[$group])) throw new \Exception(sprintf('Missing field: %s', $field));
        return $tierData[$group][$field];
    }
}
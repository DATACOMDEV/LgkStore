<?php

namespace Datacom\LgkStore\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $_store;
    protected $_encoder;
    protected $_resourceConnection;

    protected $ceeIds = null;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Framework\Url\EncoderInterface $encoder,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_store = $store;
        $this->_encoder = $encoder;
        $this->_resourceConnection = $resourceConnection;

        parent::__construct($context);

        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function getTextLinkTruncated($string, $length, $replaceText = '...') {
        if (strlen($string) <= $length) {
            return $string;
        }

        $indexToTruncate = $length-1-strlen($replaceText);
        while (substr($string, $indexToTruncate, 1) != ' ' && $indexToTruncate >= 0) {
            $indexToTruncate--;
        }

        if ($indexToTruncate <= 0)  {
            return substr($string, 0, $length).$replaceText;
        } else {
            return substr($string, 0, $indexToTruncate).$replaceText;
        }
    }

    public function getDocFileUrl($filename, $extension = 'pdf') {
        if (!is_array($extension)) {
            $extension = array($extension);
        }

        $baseFile = str_replace('#STORECODE#', $this->_store->getStore()->getCode(), BP.DIRECTORY_SEPARATOR.\Datacom\LgkStore\Model\Constants::PATH_PRODUCT_DOCS.$filename);
        $file = null;
        foreach ($extension as $ext) {
            $file = $baseFile.'.'.$ext;
            if (file_exists($file)) {
                break;
            } else {
                $file = null;
            }
        }

        if (empty($file)) {
            return '';
        }

        $retval = str_replace(
            array(
                DIRECTORY_SEPARATOR,
                ' '
            ),
            array(
                '/',
                '%20'
            ),
            substr($file, strlen(BP))
        );

        return $retval;
    }

    public function getCeeCountryIds() {
        if (is_null($this->ceeIds)) {
            $conn = $this->_resourceConnection->getConnection();

            try {
                $result = $conn->fetchCol('SELECT country_set FROM '.$this->_resourceConnection->getTableName('amasty_shipping_area').' WHERE name=\'CEE\'');
            } catch (\Exception $ex) {
                $result = $conn->fetchCol('SELECT country_set FROM '.$conn->getTableName('amasty_shipping_area').' WHERE name=\'CEE\'');
            }
            
            $this->ceeIds = [];

            foreach ($result as $r) {
                $this->ceeIds = explode(',', $r);
                break;
            }
        }

        return $this->ceeIds;
    }

    public function getStoreId() {
        return $this->_store->getStore()->getStoreId();
    }
}
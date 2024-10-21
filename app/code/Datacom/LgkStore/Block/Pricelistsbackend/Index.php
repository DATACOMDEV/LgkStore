<?php

namespace Datacom\LgkStore\Block\Pricelistsbackend;

class Index extends \Magento\Backend\Block\Template {
    
    protected $_eavConfig;

    private $manufacturers = null;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_eavConfig = $eavConfig;
    }

    public function getManufacturers() {
        return $this->_eavConfig->getAttribute('catalog_product', 'manufacturer')->getSource()->getAllOptions();
    }
}
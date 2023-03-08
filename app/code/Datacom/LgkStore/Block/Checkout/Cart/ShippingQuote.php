<?php

namespace Datacom\LgkStore\Block\Checkout\Cart;

class ShippingQuote extends \Magento\Framework\View\Element\Template {
    
    protected $_allowedCountryModel;
    protected $_countryCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Model\AllowedCountries $allowedCountryModel,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_allowedCountryModel = $allowedCountryModel;
        $this->_countryCollectionFactory = $countryCollectionFactory;
    }

    public function getAllowedCountries() {
        $fullCollection = $this->_countryCollectionFactory->create()
            ->loadByStore();
        $allowedCountries = $this->_allowedCountryModel->getAllowedCountries();

        $retval = [];
        foreach ($fullCollection as $c) {
            if (!in_array($c->getCountryId(), $allowedCountries)) continue;

            $retval[$c->getName()] = $c->getCountryId();
        }

        ksort($retval);

        return $retval;
    }
}
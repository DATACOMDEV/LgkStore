<?php

namespace Datacom\LgkStore\Plugin\Quote\Model\QuoteValidator\MinimumOrderAmount;

class ValidationMessage {

    private $_modelSession;
    private $_customerRepositoryInterface;
    private $_priceHelper;

    public function __construct(
        \Magento\Customer\Model\Session $modelSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\Pricing\Helper\Data $priceHelper
    ) {
        $this->_modelSession = $modelSession;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_priceHelper = $priceHelper;
    }

    public function afterGetMessage($subject, $result) {
        $retval = $result;

        $currentCustomer = $this->_modelSession->getCustomer();
        
        if (empty($currentCustomer)) return $retval;
        
        $currentCustomer = $this->_customerRepositoryInterface->getById($currentCustomer->getId());

        $customerOrderMinimumAmount = $currentCustomer->getCustomAttribute('dtm_customer_minimum_order_amount');
        if (!$customerOrderMinimumAmount) return $retval;
        $customerOrderMinimumAmount = $customerOrderMinimumAmount->getValue();
        if (!$customerOrderMinimumAmount) return $retval;
        if (!is_numeric($customerOrderMinimumAmount)) return $retval;

        $customerOrderMinimumAmount = $this->_priceHelper->currency(
            $customerOrderMinimumAmount,
            true,
            false
        );

        $retval = __('Minimum order amount is %1', $customerOrderMinimumAmount);

        return $retval;
    }

}
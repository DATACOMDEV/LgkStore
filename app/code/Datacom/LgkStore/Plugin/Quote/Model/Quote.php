<?php

namespace Datacom\LgkStore\Plugin\Quote\Model;

class Quote {
    
    private $_modelSession;
    private $_customerRepositoryInterface;
    private $_cart;

    public function __construct(
        \Magento\Customer\Model\Session $modelSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_modelSession = $modelSession;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_cart = $cart;
    }

    public function afterValidateMinimumAmount($subject, $result) {
        //TODO: Creare dtm_customer_minimum_order_amount da modulo attributi cliente Amasty
        
        $isValid = $result;

        if (!$isValid) return false;
        
        $currentCustomer = $this->_modelSession->getCustomer();
        
        if (empty($currentCustomer)) return $isValid;
        
        if (!$currentCustomer->getId()) return $isValid;

        $currentCustomer = $this->_customerRepositoryInterface->getById($currentCustomer->getId());

        $customerOrderMinimumAmount = $currentCustomer->getCustomAttribute('dtm_customer_minimum_order_amount');
        if (!$customerOrderMinimumAmount) return $isValid;
        $customerOrderMinimumAmount = $customerOrderMinimumAmount->getValue();
        if (!$customerOrderMinimumAmount) return $isValid;
        if (!is_numeric($customerOrderMinimumAmount)) return $isValid;

        /*$cartItems = $modelSession->getQuote()->getAllItems();
        foreach ($cartItems as $ci) {

        }*/
        
        $isValid = round($customerOrderMinimumAmount, 2) <= round($this->_cart->getQuote()->getSubtotalWithDiscount(), 2);
        return $isValid;
    }
}
<?php

namespace Datacom\LgkStore\CustomerData;

class LoggedSection implements \Magento\Customer\CustomerData\SectionSourceInterface {
    
    protected $_dtmCustomerHelper;

    public function __construct(
        \Datacom\LgkStore\Helper\Customer $dtmCustomerHelper
    ) {
        $this->_dtmCustomerHelper = $dtmCustomerHelper;
    }
    
    public function getSectionData()
    {
        return [
            'logged' => $this->_dtmCustomerHelper->isCustomerLoggedIn() ? 1 : 0,
            'customer_group_id' => $this->_dtmCustomerHelper->isCustomerLoggedIn() ? $this->_dtmCustomerHelper->getLoggedInCustomer()->getGroupId() : 0
        ];
    }
}
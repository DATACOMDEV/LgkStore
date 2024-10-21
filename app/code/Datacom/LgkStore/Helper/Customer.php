<?php

namespace Datacom\LgkStore\Helper;

class Customer extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $_session;

    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_session = $session;

        parent::__construct($context);
    }

    public function isCustomerLoggedIn() {
        return $this->_session->isLoggedIn();
    }

    public function getLoggedInCustomer() {
        return $this->_session->getCustomer();
    }

    public function isCustomerNeedPiva($customer) {
        return in_array($customer->getGroupId(), [
            \Datacom\LgkStore\Model\Constants::GROUP_ID_AZIENDA_ITALIANA,
            \Datacom\LgkStore\Model\Constants::GROUP_ID_AZIENDA_ESTERA,
            \Datacom\LgkStore\Model\Constants::GROUP_ID_RIVENDITORE_ITALIA,
            \Datacom\LgkStore\Model\Constants::GROUP_ID_RIVENDITORE_ESTERO
        ]);
    }

    public function isCustomerNeedCfisc($customer) {
        return in_array($customer->getGroupId(), [
            \Datacom\LgkStore\Model\Constants::GROUP_ID_PRIVATO_ITALIA
        ]);
    }

    public function isCustomerNeedSdi($customer) {
        return in_array($customer->getGroupId(), [
            \Datacom\LgkStore\Model\Constants::GROUP_ID_AZIENDA_ITALIANA,
            \Datacom\LgkStore\Model\Constants::GROUP_ID_RIVENDITORE_ITALIA
        ]);
    }
}
<?php

namespace Datacom\LgkStore\Plugin\Customer\Controller\Account;

class LoginPost {
    
    protected $_urlInterface;
    protected $_urlEncoder;
    protected $_redirect;
    protected $_context;

    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->_urlInterface = $urlInterface;
        $this->_urlEncoder = $urlEncoder;
        $this->_redirect = $redirect;
        $this->_context = $context;
    }

    public function afterExecute(\Magento\Customer\Controller\Account\LoginPost $subject, $result)
    {
        /*$oldRefererUrl = explode('/referer/', $this->_redirect->getRefererUrl());
        
        if (count($oldRefererUrl) < 2) {
            return $result;
        }

        $oldRefererUrl = $oldRefererUrl[1];
        $oldRefererUrl = explode('/', $oldRefererUrl);
        $oldRefererUrl = $oldRefererUrl[0];

        $result->setUrl($this->_urlInterface->getUrl('datacom/login/index', array('_current' => false, '_use_rewrite' => true, \Datacom\LgkStore\Model\Constants::CUSTOM_REFERER_QUERY_PARAM => $oldRefererUrl)));*/

        $objManager = \Magento\Framework\App\ObjectManager::getInstance();
        $session = $objManager->create('Magento\Customer\Model\Session');

        if (!$session->getCustomer() || !$session->getCustomer()->getId()) {
            return $result;
        }
        
        $result->setUrl($this->_urlInterface->getUrl('datacom/login/index', array('_current' => true, '_use_rewrite' => true)));
        return $result;
    }
}
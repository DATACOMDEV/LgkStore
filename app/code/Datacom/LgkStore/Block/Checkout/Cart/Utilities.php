<?php

namespace Datacom\LgkStore\Block\Checkout\Cart;

class Utilities extends \Magento\Framework\View\Element\Template {
    
    protected $_store;
    protected $_session;
    protected $_customerSession;
    protected $_quoteIdToMaskedQuoteId;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_store = $store;
        $this->_session = $session;
        $this->_customerSession = $customerSession;
        $this->_quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
    }

    public function getStore() {
        return $this->_store->getStore();
    }

    public function getQuoteMaskId() {
        $maskedId = null;

        if (!$this->_session->getQuote()->getId()) return null;

        try {
            $maskedId = $this->_quoteIdToMaskedQuoteId->execute($this->_session->getQuote()->getId());
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(__('Current user does not have an active cart.'));
        }
 
        return $maskedId;
    }

    public function isLoggedIn() {
        return $this->_customerSession->isLoggedIn();
    }

    public function getCustomerGroupId() {
        return $this->_customerSession->getCustomer()->getGroupId();
    }
}
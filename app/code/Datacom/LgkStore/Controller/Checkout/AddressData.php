<?php

namespace Datacom\LgkStore\Controller\Checkout;

class AddressData extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_dtmAddressData;

    /*protected $_jsonHelper;
    protected $_stockState;
    protected $_priceHelper;*/

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Datacom\LgkStore\Helper\AddressData $dtmAddressData
	)
	{
        parent::__construct($context);

        $this->_checkoutSession = $checkoutSession;
        $this->_dtmAddressData = $dtmAddressData;

        //$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /*$this->_jsonHelper = $this->_objectManager->create('Magento\Framework\Json\Helper\Data');
        $this->_stockState = $this->_objectManager->create('Magento\CatalogInventory\Api\StockStateInterface');
        $this->_priceHelper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');*/
	}

	public function execute()
	{
        $this->_execute();

        $this->getResponse()->setNoCacheHeaders();
		//$this->getResponse()->setHeader('Content-type', 'application/json');
		//$this->getResponse()->setHttpResponseCode(201);
		//$this->getResponse()->setBody($response);

		return;
    }

    private function _execute() {
        $quote = $this->_checkoutSession->getQuote();

        if (!$quote) return;

        if (!$quote->getId()) return;

        $shippingCFisc = $this->getRequest()->getParam('shippingCFisc');
        if (empty($shippingCFisc)) {
            $shippingCFisc = '/';
        }

        $billingCFisc = $this->getRequest()->getParam('billingCFisc');
        if (empty($billingCFisc)) {
            $billingCFisc = '/';
        }

        $shippingSdiPec = $this->getRequest()->getParam('shippingSdiPec');
        if (empty($shippingSdiPec)) {
            $shippingSdiPec = '/';
        }

        $billingSdiPec = $this->getRequest()->getParam('billingSdiPec');
        if (empty($billingSdiPec)) {
            $billingSdiPec = '/';
        }

        $this->_dtmAddressData->save($quote->getId(), $shippingCFisc, $billingCFisc, $shippingSdiPec, $billingSdiPec);
    }
}

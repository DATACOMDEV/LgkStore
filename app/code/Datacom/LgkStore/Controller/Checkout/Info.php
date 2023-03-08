<?php

namespace Datacom\LgkStore\Controller\Checkout;

class Info extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_jsonHelper;
    protected $_stockState;
    protected $_priceHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context
	)
	{
        parent::__construct($context);

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_checkoutSession = $this->_objectManager->create('Magento\Checkout\Model\Session');
        $this->_jsonHelper = $this->_objectManager->create('Magento\Framework\Json\Helper\Data');
        $this->_stockState = $this->_objectManager->create('Magento\CatalogInventory\Api\StockStateInterface');
        $this->_priceHelper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');
	}

	public function execute()
	{
        $response = [
            'errors' => []
        ];

        $response['content'] = $this->getResponseData();

        $response = $this->_jsonHelper->jsonEncode($response);

        $this->getResponse()->setNoCacheHeaders();
		$this->getResponse()->setHeader('Content-type', 'application/json');
		//$this->getResponse()->setHttpResponseCode(201);
		$this->getResponse()->setBody($response);

		return;
    }
    
    private function getResponseData() {
        $quote = $this->_checkoutSession->getQuote();

        return [
            'grand_total' => $this->_priceHelper->currency($quote->getGrandTotal(), true, false)
        ];
    }
}

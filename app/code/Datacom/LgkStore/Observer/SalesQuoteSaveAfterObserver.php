<?php

namespace Datacom\LgkStore\Observer;

class SalesQuoteSaveAfterObserver implements \Magento\Framework\Event\ObserverInterface {
    
    protected $_checkoutSession;
    protected $_resourceConnection;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_resourceConnection = $resourceConnection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        file_put_contents(dirname(__FILE__).'/test.txt', "ci passo 1\r\n", FILE_APPEND);
        
        $event = $observer->getEvent();

        if (!$event) return;

        $order = $observer->getEvent()->getOrder();

        if (!$order) return;

        $quote = $order->getQuote();

        if (!$quote) return;

        file_put_contents(dirname(__FILE__).'/test.txt', print_r($quote->getData(), true)."\r\n", FILE_APPEND);

        /*$address = $observer->getCustomerAddress();

        file_put_contents(dirname(__FILE__).'/test.txt', "ci passo 2\r\n", FILE_APPEND);

        file_put_contents(dirname(__FILE__).'/test.txt', print_r($address->getData(), true)."\r\n", FILE_APPEND);*/

        /*$quote = $observer->getEvent()->getQuote();

        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress) return;

        $conn = $this->_resourceConnection->getConnection();*/
        
        //file_put_contents(dirname(__FILE__).'/test.txt', print_r($quote->getShippingAddress()->getData(), true)."\r\n", FILE_APPEND);
        /*try {
            //file_put_contents(dirname(__FILE__).'/test.txt', print_r($quote->getShippingAddress()->getData(), true)."\r\n", FILE_APPEND);
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            $this->_resourceConnection->closeConnection();
        }*/
    }
}
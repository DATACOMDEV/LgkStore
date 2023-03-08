<?php

namespace Datacom\LgkStore\Model;

class Layout  implements \Magento\Framework\Event\ObserverInterface
{
    protected $_logger;
    public function __construct ( \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return $this;
        $xml = $observer->getEvent()->getLayout()->getXmlString();
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/layout_block.xml');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($xml);
        return $this;
    }
}
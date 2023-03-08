<?php
declare(strict_types=1);

namespace Datacom\Preventivo\Model\Payment;

class Preventivo extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "preventivo";
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}


<?php

namespace Datacom\LgkStore\Observer;

class OnEmailOrderSetTemplateVarsBeforeObserver implements \Magento\Framework\Event\ObserverInterface {
    
    protected $_customerRepository;
    protected $_dtmAddressHelper;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Datacom\LgkStore\Helper\AddressData $dtmAddressHelper
    ) {
        $this->_customerRepository = $customerRepository;
        $this->_dtmAddressHelper = $dtmAddressHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getEvent()->getTransport();
        $order = $transport->getOrder();
        
        if ($order == null) return;

        $customerId = $order->getCustomerId();

        if ($customerId) {
            try {
                $customer = $this->_customerRepository->getById($customerId);
                if (empty($customer) || !$customer->getId()) throw new \Exception();
            } catch (\Exception $ex) {
                return;
            }
        } else {
            $customer = null;
        }

        if (is_null($customer)) {
            $addressData = $this->_dtmAddressHelper->get($order->getQuoteId());
            if (empty($addressData)) return;
            $codiceFiscaleCliente = $addressData['shipping_c_fiscale'];
        } else {
            $codiceFiscaleCliente = $customer->getCustomAttribute('c_fiscale');
            if ($codiceFiscaleCliente) {
                $codiceFiscaleCliente = $codiceFiscaleCliente->getValue();
            }
        }

        if (empty($codiceFiscaleCliente) || $codiceFiscaleCliente == '/') return;

        $transport['c_fiscale'] = $codiceFiscaleCliente;
    }
}
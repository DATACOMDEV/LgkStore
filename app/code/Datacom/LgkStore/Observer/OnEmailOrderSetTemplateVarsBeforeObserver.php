<?php

namespace Datacom\LgkStore\Observer;

class OnEmailOrderSetTemplateVarsBeforeObserver implements \Magento\Framework\Event\ObserverInterface {
    
    protected $_customerRepository;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->_customerRepository = $customerRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getEvent()->getTransport();
        $order = $transport->getOrder();
        
        if ($order != null)
        {
            $customerId = $order->getCustomerId();

            try {
                $customer = $this->_customerRepository->getById($customerId);
                if (empty($customer) || !$customer->getId()) throw new \Exception();
            } catch (\Exception $ex) {
                return;
            }
            
            $codiceFiscaleCliente = $customer->getCustomAttribute('c_fiscale');
            if ($codiceFiscaleCliente) {
                $codiceFiscaleCliente = $codiceFiscaleCliente->getValue();
            }

            if (empty($codiceFiscaleCliente) || $codiceFiscaleCliente == '/') return;

            $transport['c_fiscale'] = $codiceFiscaleCliente;
        }
    }
}
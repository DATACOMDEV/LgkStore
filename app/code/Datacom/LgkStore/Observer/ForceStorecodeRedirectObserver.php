<?php

namespace Datacom\LgkStore\Observer;

class ForceStorecodeRedirectObserver implements \Magento\Framework\Event\ObserverInterface {
    protected $storeManager;
    protected $url;
    /** @var string $defaultStorecode */
    protected $defaultStorecode = 'it';
    /** @var array $storeCodes - array of existing storecodes*/
    protected $storeCodes = [];

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $url
    ) {
       $this->storeManager = $storeManager;
       $this->url = $url;
       $this->storeCodes = array_keys($this->storeManager->getStores(false, true));
       //$this->storeCodes = array_merge(array_keys($this->storeManager->getStores(false, true)), ['admin_10kq5z']);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
         $urlParts = parse_url($this->url->getCurrentUrl());
         $path = $urlParts['path'];

         if ($path == '/') return;

         // get storecode from URL
         $urlCode = trim(substr($path, 0, 4), '/');

         // If path does not already contain an existing storecode
         if (!in_array($urlCode, $this->storeCodes)) {
             $path = ltrim($path, '/');
             if ($path == 'en') {
                 $path = '';
             }

             // Redirect to URL including storecode
             header("HTTP/1.1 301 Moved Permanently");
             header("Location: " . $this->storeManager->getStore()->getBaseUrl() . $path);
             exit();
       }
    }
}
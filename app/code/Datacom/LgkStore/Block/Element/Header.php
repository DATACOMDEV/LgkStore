<?php

namespace Datacom\LgkStore\Block\Element;

class Header extends \Magento\Theme\Block\Html\Header {
    
    //protected $_cache;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        //$this->_cache = $objectManager->create('Magento\Framework\App\Cache');
    }

    public function getCacheKeyInfo()
    {
        return [
            'BLOCK_TPL',
            $this->_storeManager->getStore()->getCode(),
            $this->getTemplateFile(),
            //'base_url' => $this->getBaseUrl(),
            'template' => $this->getTemplate()
        ];
    }

    protected function getCacheLifetime()
    {
        //return 604800;
        return 0;
    }

    public function getCacheData($key) {
        return $this->_cache->load($key);
    }

    public function setCacheData($key, $data) {
        $this->_cache->save($data, $key);
    }

    public function getStoreCode() {
        return $this->_storeManager->getStore()->getCode();
    }
}
<?php

namespace Datacom\LgkStore\Helper;

class Url extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $_store;
    protected $_categoryRepository;
    protected $_productRepository;
    protected $_registry;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_store = $store;
        $this->_categoryRepository = $categoryRepository;
        $this->_productRepository = $productRepository;
        $this->_registry = $registry;
        
        parent::__construct($context);

        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    protected function getCurrentCategory() {
        return $this->_registry->registry('current_category');
    }

    public function getCategoryUrl($categoryId = null) {
        if (empty($categoryId)) {
            $categoryId = $this->getCurrentCategory()->getId();
        }

        $curStoreCode = $this->_store->getStore()->getCode();

        $urls = [];
        $stores = $this->_store->getStores();
        foreach ($stores as $store) {
            if (!$store->getIsActive()) continue;
            $cat = $this->_categoryRepository->get($categoryId, $store->getId());

            if (!$cat->getIsActive()) continue;

            $urls[$store->getCode()] = str_replace('/'.$curStoreCode.'/', '/'.$store->getCode().'/', $cat->getUrl());

            if (strpos($urls[$store->getCode()], 'catalog/category/view/s/default-category/id/2/') !== false) {
                $urls[$store->getCode()] = str_replace('/'.$curStoreCode.'/', '/'.$store->getCode().'/', $this->_store->getStore()->getCurrentUrl());
                $urls[$store->getCode()] = explode('?', $urls[$store->getCode()]);
                $urls[$store->getCode()] = $urls[$store->getCode()][0];
            }
        }

        return $urls;
    }

    protected function getCurrentProduct() {
        return $this->_registry->registry('current_product');
    }

    public function getProductUrl($productId = null) {
        if (empty($productId)) {
            $productId = $this->getCurrentProduct()->getId();
        }

        $curStoreCode = $this->_store->getStore()->getCode();

        $urls = [];
        $stores = $this->_store->getStores();
        foreach ($stores as $store) {
            if (!$store->getIsActive()) continue;
            $prod = $this->_productRepository->getById($productId, false, $store->getId());

            if (!$prod->getIsActive()) continue;

            $urls[$store->getCode()] = str_replace('/'.$curStoreCode.'/', '/'.$store->getCode().'/', $prod->getProductUrl());
        }

        return $urls;
    }
}
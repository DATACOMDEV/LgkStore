<?php

namespace Datacom\LgkStore\Helper;

class Category extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $_storeManager;
    protected $_categoryRepository;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_storeManager = $storeManager;
        $this->_categoryRepository = $categoryRepository;

        parent::__construct($context);
    }

    public function getCategory($id) {
        return $this->_categoryRepository->get($id, $this->_storeManager->getStore()->getId());
    }
}
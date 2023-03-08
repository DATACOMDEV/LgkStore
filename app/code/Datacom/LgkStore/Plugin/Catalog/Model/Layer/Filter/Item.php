<?php

namespace Datacom\LgkStore\Plugin\Catalog\Model\Layer\Filter;

class Item {
    protected $_storeManager;
    protected $_categoryRepository;
    protected $_url;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Framework\UrlInterface $url
    )
    {
        $this->_storeManager = $storeManager;
        $this->_categoryRepository = $categoryRepository;
        $this->_url = $url;
    }

    protected function getQueryParam()
    {
        $request = $this->_url->getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true));
        $returnQueryData = array();

        if(strrpos($request,'?') !== false ){
            $queryData = explode('&', substr($request,strrpos($request,'?') + 1));

            foreach ($queryData as $qd) {
                if (substr($qd, 0, 2 ) === "q=") {
                    continue;
                }
                $returnQueryData[] = $qd;
            }
        }
        
        return implode('&', $returnQueryData);
    }

    public function aroundGetUrl(\Magento\Catalog\Model\Layer\Filter\Item $subject, \Closure $proceed)
    {
        /*$returnValue = null;
        
        if ($subject->getFilter()->getRequestVar() == 'cat') {
            $categoryId = $subject->getValue();

            if (!empty($categoryId)) {
                $category = $this->_categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
                $returnValue = $category->getUrl();
                $queryUrl = $this->getQueryParam();
                if (!empty($queryUrl)) {
                    $returnValue .= $queryUrl;
                }

                if (substr_count($returnValue, '?') > 1) {
                    $returnValue = explode('?', $returnValue);
                    $returnValue = $returnValue[0] . '?' . $returnValue[1];
                }
            }
        }

        if (is_null($returnValue)) {*/
            $returnValue = $proceed();
        /*}*/

        return $returnValue;
    }
}
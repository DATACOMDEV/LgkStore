<?php

namespace Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend;

class Index extends \Magento\Backend\App\Action {
    protected $resultPageFactory = false;    

    public function __construct(
            \Magento\Backend\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
            parent::__construct($context);
            $this->resultPageFactory = $resultPageFactory;
    } 

    public function execute()
    {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Datacom_LgkStore::menu');
            $resultPage->getConfig()->getTitle()->prepend(__('Aggiornamento prezzi'));
            return $resultPage;
    }

    protected function _isAllowed()
    {
        //return true;
        return $this->_authorization->isAllowed('Datacom_LgkStore::menu');
    }
}
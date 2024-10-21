<?php

namespace Datacom\LgkStore\Controller\Adminhtml\Pricelistsbackend;

class BackupListing extends \Magento\Backend\App\Action {
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
        $resultPage->getConfig()->getTitle()->prepend(__('Backup aggiornamento prezzi'));

        $backupDate = $this->getRequest()->getParam('backup_date');
        if (!empty($backupDate)) {
            $resultPage->getLayout()
            ->getBlock('dtm_lgk_backup_listing')
            ->setData('backup_date', $backupDate);
        }

        return $resultPage;
    }

    protected function _isAllowed()
    {
        //return true;
        return $this->_authorization->isAllowed('Datacom_LgkStore::menu');
    }
}
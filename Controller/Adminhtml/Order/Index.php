<?php
namespace O2TI\PreOrder\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Pre Orders'));
        $resultPage->setActiveMenu('O2TI_PreOrder::preorder');
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('O2TI_PreOrder::preorder');
    }
}
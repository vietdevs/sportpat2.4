<?php


namespace Olegnax\Athlete2\Controller\Adminhtml\Import;

class Index extends \Magento\Backend\App\Action
{
	const ADMIN_RESOURCE = 'Olegnax_Athlete2::import';

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend( (__( 'Athlete2 Demo Import' ) ) );

		return $resultPage;
	}
}

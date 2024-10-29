<?php

namespace O2TI\PreOrder\Controller\Adminhtml\Create;

use O2TI\PreOrder\Model\QuoteSender;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;
use Throwable;
use O2TI\PreOrder\Model\PreOrderRepository;
use O2TI\PreOrder\Model\PreOrderFactory;
use Magento\Framework\Math\Random;
use Magento\Backend\Model\Auth\Session as AdminSession;

class Index extends Action
{
    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var QuoteRepository */
    private $quoteRepository;

    /** @var QuoteSender */
    private $quoteSender;

    /** @var LoggerInterface */
    private $logger;

    /** @var  PreOrderRepository */
    protected $preOrderRepository;

    /** @var  PreOrderFactory */
    protected $preOrderFactory;

    /** @var  Random */
    protected $mathRandom;

    /** @var AdminSession */
    protected $adminSession;

    /**
     * Index constructor.
     *
     * @param JsonFactory $resultJsonFactory
     * @param QuoteRepository $quoteRepository
     * @param QuoteSender $quoteSender
     * @param Context $context
     * @param LoggerInterface $logger
     * @param PreOrderRepository $preOrderRepository
     * @param PreOrderFactory $preOrderFactory
     * @param Random $mathRandom
     * @param AdminSession $adminSession
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        QuoteRepository $quoteRepository,
        QuoteSender $quoteSender,
        Context $context,
        LoggerInterface $logger,
        PreOrderRepository $preOrderRepository,
        PreOrderFactory $preOrderFactory,
        Random $mathRandom,
        AdminSession $adminSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteSender = $quoteSender;
        $this->logger = $logger;
        $this->preOrderRepository = $preOrderRepository;
        $this->preOrderFactory = $preOrderFactory;
        $this->mathRandom = $mathRandom;
        $this->adminSession = $adminSession;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $request = $this->getRequest();
            // get quote
            $quote = $this->quoteRepository->get($request->getParam('quote_id'));
           
            $hash = sha1($this->mathRandom->getRandomString(100));
            $hash = $this->getBase64UrlEncode($hash);

            // Get admin user information
            $adminUser = $this->adminSession->getUser();
            $adminUsername = $adminUser ? $adminUser->getUsername() : 'system';

            $data = [
                "customer_id" => $quote->getCustomerId(),
                "quote_id" => $quote->getId(),
                "hash" => $hash,
                "admin" => $adminUsername,
            ];
            
            $this->savePreOrder($data);
            $this->quoteSender->send($quote, $hash);

            $this->messageManager->addSuccessMessage(__('The PreOrder email has been sent.'));

            return $resultJson->setData([
                'success' => true,
                'redirect_url' => $this->getUrl('preorder/order/index')
            ]);
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage(), ['exception' => $t]);
            $this->messageManager->addErrorMessage(__('Error sending PreOrder email. Please try again.'));
            
            return $resultJson->setData([
                'success' => false
            ]);
        }
    }

    /**
     * Save PreOrder
     *
     * @param array $data
     * @return void
     */
    public function savePreOrder($data)
    {
        $preOrder = $this->preOrderFactory->create();
        $preOrder->setData($data);
        $this->preOrderRepository->save($preOrder);
    }

    /**
     * Get Base64 Url Encode
     *
     * @param string $code
     * @return string
     */
    public function getBase64UrlEncode($code)
    {
        return rtrim(strtr(base64_encode($code), '+/', '-_'), '=');
    }
}
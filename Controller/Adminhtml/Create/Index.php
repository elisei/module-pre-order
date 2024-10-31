<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace O2TI\PreOrder\Controller\Adminhtml\Create;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Math\Random;
use Magento\Quote\Model\QuoteRepository;
use O2TI\PreOrder\Helper\Data as HelperConfig;
use O2TI\PreOrder\Model\PreOrderFactory;
use O2TI\PreOrder\Model\PreOrderRepository;
use O2TI\PreOrder\Model\QuoteSender;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller responsible for creating and sending pre-order emails
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var QuoteSender
     */
    private $quoteSender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PreOrderRepository
     */
    private $preOrderRepository;

    /**
     * @var PreOrderFactory
     */
    private $preOrderFactory;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var AdminSession
     */
    private $adminSession;

    /**
     * @var HelperConfig
     */
    private $helperConfig;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param QuoteRepository $quoteRepository
     * @param QuoteSender $quoteSender
     * @param LoggerInterface $logger
     * @param PreOrderRepository $preOrderRepository
     * @param PreOrderFactory $preOrderFactory
     * @param Random $mathRandom
     * @param AdminSession $adminSession
     * @param HelperConfig $helperConfig
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteRepository $quoteRepository,
        QuoteSender $quoteSender,
        LoggerInterface $logger,
        PreOrderRepository $preOrderRepository,
        PreOrderFactory $preOrderFactory,
        Random $mathRandom,
        AdminSession $adminSession,
        HelperConfig $helperConfig
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
        $this->helperConfig = $helperConfig;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $quote = $this->getQuoteFromRequest();
            $hash = $this->generateHash();
            $adminData = $this->getAdminData();

            $preOrderData = [
                'customer_id' => $quote->getCustomerId(),
                'quote_id' => $quote->getId(),
                'hash' => $hash,
                'admin' => $adminData['username'],
                'tracking' => $adminData['tracking'],
            ];
            $this->savePreOrder($preOrderData);
            $this->quoteSender->send($quote, $hash, $adminData['tracking']);

            $this->messageManager->addSuccessMessage(
                __('The PreOrder email has been sent.')
            );

            return $resultJson->setData([
                'success' => true,
                'redirect_url' => $this->getUrl('preorder/order/index')
            ]);
        } catch (Throwable $exc) {
            $this->logger->error($exc->getMessage(), ['exception' => $exc]);
            $this->messageManager->addErrorMessage(
                __('Error sending PreOrder email. Please try again.')
            );
            
            return $resultJson->setData(['success' => false]);
        }
    }

    /**
     * Save PreOrder data
     *
     * @param array $data
     * @return void
     */
    private function savePreOrder(array $data): void
    {
        $preOrder = $this->preOrderFactory->create();
        $preOrder->setData($data);
        $this->preOrderRepository->save($preOrder);
    }

    /**
     * Get Base64 URL encoded string
     *
     * @param string $code
     * @return string
     */
    private function getBase64UrlEncode(string $code): string
    {
        return rtrim(strtr(base64_encode($code), '+/', '-_'), '=');
    }

    /**
     * Generate unique hash for pre-order
     *
     * @return string
     */
    private function generateHash(): string
    {
        $randomString = $this->mathRandom->getRandomString(100);
        return $this->getBase64UrlEncode(sha1($randomString));
    }

    /**
     * Get quote from request
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuoteFromRequest()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        return $this->quoteRepository->get($quoteId);
    }

    /**
     * Get admin user data including tracking information
     *
     * @return array
     */
    private function getAdminData(): array
    {
        $adminUser = $this->adminSession->getUser();
        $username = $adminUser ? $adminUser->getUsername() : 'system';
        
        return [
            'username' => $username,
            'tracking' => $this->helperConfig->getTrackingByAdmin($username)
        ];
    }
}

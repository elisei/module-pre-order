<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use O2TI\PreOrder\Api\Data\GuestPreOrderInterface;
use O2TI\PreOrder\Api\GuestPreOrderManagementInterface;
use O2TI\PreOrder\Model\PreOrderFactory;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as PreOrderResource;
use Psr\Log\LoggerInterface;

/**
 * Guest PreOrder Management API implementation
 */
class GuestPreOrderManagement implements GuestPreOrderManagementInterface
{
    /**
     * @var PreOrderFactory
     */
    private $preOrderFactory;

    /**
     * @var PreOrderResource
     */
    private $preOrderResource;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @param PreOrderFactory $preOrderFactory
     * @param PreOrderResource $preOrderResource
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param LoggerInterface $logger
     * @param Random $mathRandom
     */
    public function __construct(
        PreOrderFactory $preOrderFactory,
        PreOrderResource $preOrderResource,
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        LoggerInterface $logger,
        Random $mathRandom
    ) {
        $this->preOrderFactory = $preOrderFactory;
        $this->preOrderResource = $preOrderResource;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->logger = $logger;
        $this->mathRandom = $mathRandom;
    }

    /**
     * @inheritdoc
     */
    public function getPreOrderByHash($hash)
    {
        try {
            $preOrder = $this->preOrderFactory->create();
            $this->preOrderResource->loadByHash($preOrder, $hash);
            
            if (!$preOrder->getId()) {
                throw new NoSuchEntityException(__('Pre-order with hash "%1" does not exist.', $hash));
            }
            
            return $preOrder;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e, 'hash' => $hash]);
            throw new NoSuchEntityException(
                __('Could not load pre-order with hash "%1": %2', $hash, $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getQuoteByPreOrderHash($hash)
    {
        try {
            $preOrder = $this->getPreOrderByHash($hash);
            $quoteId = $preOrder->getQuoteId();
            
            if (!$quoteId) {
                throw new NoSuchEntityException(__('Pre-order with hash "%1" has no associated quote.', $hash));
            }
            
            $quote = $this->quoteRepository->get($quoteId);
            
            if (!$quote->getId()) {
                throw new NoSuchEntityException(__('Quote with ID "%1" does not exist.', $quoteId));
            }

            return $quote;
        } catch (NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e, 'hash' => $hash]);
            throw new NoSuchEntityException(
                __('Could not load quote for pre-order with hash "%1": %2', $hash, $e->getMessage()),
                $e
            );
        }
    }
    
    /**
     * @inheritdoc
     */
    public function createGuestPreOrder(GuestPreOrderInterface $preOrderData)
    {
        try {
            $hash = $preOrderData->getHash() ?: $this->generateHash();
            
            $quoteId = $preOrderData->getQuoteId();
            if ($quoteId) {
                try {
                    $this->quoteRepository->get($quoteId);
                } catch (NoSuchEntityException $e) {
                    throw new NoSuchEntityException(__('Quote with ID "%1" does not exist.', $quoteId));
                }
            }
            
            $preOrder = $this->preOrderFactory->create();
            $preOrder->setData([
                'customer_id' => $preOrderData->getCustomerId(),
                'quote_id' => $quoteId,
                'hash' => $hash,
                'admin' => $preOrderData->getAdmin() ?: 'guest-api',
                'tracking' => $preOrderData->getTracking() ?: ''
            ]);
            
            $this->preOrderResource->save($preOrder);
            
            return $preOrder;
        } catch (NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new CouldNotSaveException(
                __('Could not save guest pre-order: %1', $e->getMessage()),
                $e
            );
        }
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
     * Get Base64 URL encoded string
     *
     * @param string $code
     * @return string
     */
    private function getBase64UrlEncode(string $code): string
    {
        return rtrim(strtr(base64_encode($code), '+/', '-_'), '=');
    }
}

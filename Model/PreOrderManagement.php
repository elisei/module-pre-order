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

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use O2TI\PreOrder\Api\PreOrderManagementInterface;
use O2TI\PreOrder\Api\Data\PreOrderInterface;
use O2TI\PreOrder\Api\Data\PreOrderInterfaceFactory;
use O2TI\PreOrder\Model\PreOrderFactory;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as PreOrderResource;
use O2TI\PreOrder\Model\ResourceModel\PreOrder\CollectionFactory as PreOrderCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * PreOrder Management API implementation
 */
class PreOrderManagement implements PreOrderManagementInterface
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
     * @var PreOrderInterfaceFactory
     */
    private $preOrderInterfaceFactory;

    /**
     * @var PreOrderCollectionFactory
     */
    private $preOrderCollectionFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PreOrderFactory $preOrderFactory
     * @param PreOrderResource $preOrderResource
     * @param PreOrderInterfaceFactory $preOrderInterfaceFactory
     * @param PreOrderCollectionFactory $preOrderCollectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        PreOrderFactory $preOrderFactory,
        PreOrderResource $preOrderResource,
        PreOrderInterfaceFactory $preOrderInterfaceFactory,
        PreOrderCollectionFactory $preOrderCollectionFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        LoggerInterface $logger
    ) {
        $this->preOrderFactory = $preOrderFactory;
        $this->preOrderResource = $preOrderResource;
        $this->preOrderInterfaceFactory = $preOrderInterfaceFactory;
        $this->preOrderCollectionFactory = $preOrderCollectionFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function createPreOrder(PreOrderInterface $preOrder)
    {
        try {
            $preOrderModel = $this->preOrderFactory->create();
            $preOrderModel->setCustomerId($preOrder->getCustomerId());
            $preOrderModel->setQuoteId($preOrder->getQuoteId());
            $preOrderModel->setHash($preOrder->getHash());
            $preOrderModel->setAdmin($preOrder->getAdmin());
            $preOrderModel->setTracking($preOrder->getTracking());
            
            $this->preOrderResource->save($preOrderModel);
            
            $preOrderId = $preOrderModel->getId();
            $result = $this->getPreOrderById($preOrderId);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new CouldNotSaveException(
                __('Could not save pre-order: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getPreOrderById($entityId)
    {
        $preOrder = $this->preOrderFactory->create();
        $this->preOrderResource->load($preOrder, $entityId);
        
        if (!$preOrder->getId()) {
            throw new NoSuchEntityException(__('Pre-order with ID "%1" does not exist.', $entityId));
        }
        
        return $preOrder;
    }

    /**
     * @inheritdoc
     */
    public function getPreOrderByHash($hash)
    {
        $preOrder = $this->preOrderFactory->create();
        $this->preOrderResource->loadByHash($preOrder, $hash);
        
        if (!$preOrder->getId()) {
            throw new NoSuchEntityException(__('Pre-order with hash "%1" does not exist.', $hash));
        }
        
        return $preOrder;
    }

    /**
     * @inheritdoc
     */
    public function updatePreOrder($entityId, PreOrderInterface $preOrder)
    {
        try {
            $existingPreOrder = $this->getPreOrderById($entityId);
            
            $existingPreOrder->setCustomerId($preOrder->getCustomerId());
            $existingPreOrder->setQuoteId($preOrder->getQuoteId());
            $existingPreOrder->setHash($preOrder->getHash());
            $existingPreOrder->setAdmin($preOrder->getAdmin());
            $existingPreOrder->setTracking($preOrder->getTracking());
            
            $this->preOrderResource->save($existingPreOrder);
            
            return $this->getPreOrderById($entityId);
        } catch (NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new CouldNotSaveException(
                __('Could not update pre-order: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function deletePreOrder($entityId)
    {
        try {
            $preOrder = $this->getPreOrderById($entityId);
            $this->preOrderResource->delete($preOrder);
            return true;
        } catch (NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new CouldNotDeleteException(
                __('Could not delete pre-order: %1', $e->getMessage()),
                $e
            );
        }
    }
}

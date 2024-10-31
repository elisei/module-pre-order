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

namespace O2TI\PreOrder\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use O2TI\PreOrder\Api\PreOrderRepositoryInterface;
use O2TI\PreOrder\Model\PreOrder;
use O2TI\PreOrder\Model\PreOrderFactory;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as ResourceModel;

/**
 * Repository for managing PreOrder entities
 */
class PreOrderRepository implements PreOrderRepositoryInterface
{
    /**
     * @var PreOrderFactory
     */
    private PreOrderFactory $preOrderFactory;

    /**
     * @var ResourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @param PreOrderFactory $preOrderFactory
     * @param ResourceModel $resourceModel
     */
    public function __construct(
        PreOrderFactory $preOrderFactory,
        ResourceModel $resourceModel
    ) {
        $this->preOrderFactory = $preOrderFactory;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @inheritdoc
     */
    public function save(PreOrder $preOrder)
    {
        try {
            $this->resourceModel->save($preOrder);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $preOrder;
    }

    /**
     * @inheritdoc
     */
    public function getById($preOrderId)
    {
        $preOrder = $this->preOrderFactory->create();
        $this->resourceModel->load($preOrder, $preOrderId);
        if (!$preOrder->getId()) {
            throw new NoSuchEntityException(
                __('The pre-order with the "%1" ID doesn\'t exist.', $preOrderId)
            );
        }
        return $preOrder;
    }

    /**
     * @inheritdoc
     */
    public function delete(PreOrder $preOrder)
    {
        try {
            $this->resourceModel->delete($preOrder);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($preOrderId)
    {
        return $this->delete($this->getById($preOrderId));
    }

    /**
     * @inheritdoc
     */
    public function getByHash($hash)
    {
        $preOrder = $this->preOrderFactory->create();
        $this->resourceModel->loadByHash($preOrder, $hash);
        if (!$preOrder->getQuoteId()) {
            throw new NoSuchEntityException(
                __('The pre-order with the hash "%1" doesn\'t exist.', $hash)
            );
        }
        return $preOrder;
    }
}

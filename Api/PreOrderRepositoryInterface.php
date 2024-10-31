<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use O2TI\PreOrder\Model\PreOrder;

interface PreOrderRepositoryInterface
{
    /**
     * Save pre-order.
     *
     * @param PreOrder $preOrder
     * @return PreOrder
     * @throws CouldNotSaveException
     */
    public function save(PreOrder $preOrder);

    /**
     * Get pre-order by ID.
     *
     * @param int $preOrderId
     * @return PreOrder
     * @throws NoSuchEntityException
     */
    public function getById($preOrderId);

    /**
     * Delete pre-order.
     *
     * @param PreOrder $preOrder
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(PreOrder $preOrder);

    /**
     * Delete pre-order by ID.
     *
     * @param int $preOrderId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($preOrderId);

    /**
     * Get pre-order by hash.
     *
     * @param string $hash
     * @return PreOrder
     * @throws NoSuchEntityException
     */
    public function getByHash($hash);
}

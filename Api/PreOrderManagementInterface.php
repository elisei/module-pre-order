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

/**
 * Interface for PreOrder management via REST API
 * @api
 */
interface PreOrderManagementInterface
{
    /**
     * Create a new pre-order
     *
     * @param \O2TI\PreOrder\Api\Data\PreOrderInterface $preOrder
     * @return \O2TI\PreOrder\Api\Data\PreOrderInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createPreOrder(\O2TI\PreOrder\Api\Data\PreOrderInterface $preOrder);

    /**
     * Get a pre-order by ID
     *
     * @param int $entityId
     * @return \O2TI\PreOrder\Api\Data\PreOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPreOrderById($entityId);

    /**
     * Get a pre-order by hash
     *
     * @param string $hash
     * @return \O2TI\PreOrder\Api\Data\PreOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPreOrderByHash($hash);

    /**
     * Update an existing pre-order
     *
     * @param int $entityId
     * @param \O2TI\PreOrder\Api\Data\PreOrderInterface $preOrder
     * @return \O2TI\PreOrder\Api\Data\PreOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function updatePreOrder($entityId, \O2TI\PreOrder\Api\Data\PreOrderInterface $preOrder);

    /**
     * Delete a pre-order by ID
     *
     * @param int $entityId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deletePreOrder($entityId);
}

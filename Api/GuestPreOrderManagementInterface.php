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
 * Interface for Guest PreOrder management via REST API
 * @api
 */
interface GuestPreOrderManagementInterface
{
    /**
     * Get a pre-order by hash for guest users
     *
     * @param string $hash
     * @return \O2TI\PreOrder\Api\Data\PreOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPreOrderByHash($hash);
    
    /**
     * Get quote information associated with a pre-order by hash
     *
     * @param string $hash
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteByPreOrderHash($hash);
    
    /**
     * Create a new pre-order as guest
     *
     * @param \O2TI\PreOrder\Api\Data\GuestPreOrderInterface $preOrderData
     * @return \O2TI\PreOrder\Api\Data\PreOrderInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createGuestPreOrder(\O2TI\PreOrder\Api\Data\GuestPreOrderInterface $preOrderData);
}
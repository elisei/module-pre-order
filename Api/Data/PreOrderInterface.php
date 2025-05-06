<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Api\Data;

/**
 * Interface for PreOrder data
 * @api
 */
interface PreOrderInterface
{
    /**
     * Constants for keys of data array
     */
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const QUOTE_ID = 'quote_id';
    const HASH = 'hash';
    const ADMIN = 'admin';
    const TRACKING = 'tracking';
    const CREATED_AT = 'created_at';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId();

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Set customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get quote ID
     *
     * @return int|null
     */
    public function getQuoteId();

    /**
     * Set quote ID
     *
     * @param int|null $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash();

    /**
     * Set hash
     *
     * @param string $hash
     * @return $this
     */
    public function setHash($hash);

    /**
     * Get admin username
     *
     * @return string
     */
    public function getAdmin();

    /**
     * Set admin username
     *
     * @param string $admin
     * @return $this
     */
    public function setAdmin($admin);

    /**
     * Get tracking code
     *
     * @return string
     */
    public function getTracking();

    /**
     * Set tracking code
     *
     * @param string $tracking
     * @return $this
     */
    public function setTracking($tracking);

    /**
     * Get created at timestamp
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at timestamp
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
}

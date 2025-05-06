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
 * Interface for Guest PreOrder data
 * @api
 */
interface GuestPreOrderInterface
{
    /**
     * Constants for keys of data array
     */
    const CUSTOMER_ID = 'customer_id';
    const QUOTE_ID = 'quote_id';
    const HASH = 'hash';
    const ADMIN = 'admin';
    const TRACKING = 'tracking';

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
     * @return string|null
     */
    public function getHash();

    /**
     * Set hash
     *
     * @param string|null $hash
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
}

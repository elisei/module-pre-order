<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use O2TI\PreOrder\Api\Data\GuestPreOrderInterface;

/**
 * Guest PreOrder data model
 */
class GuestPreOrder extends AbstractExtensibleObject implements GuestPreOrderInterface
{
    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getQuoteId()
    {
        return $this->_get(self::QUOTE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritdoc
     */
    public function getHash()
    {
        return $this->_get(self::HASH);
    }

    /**
     * @inheritdoc
     */
    public function setHash($hash)
    {
        return $this->setData(self::HASH, $hash);
    }

    /**
     * @inheritdoc
     */
    public function getAdmin()
    {
        return $this->_get(self::ADMIN);
    }

    /**
     * @inheritdoc
     */
    public function setAdmin($admin)
    {
        return $this->setData(self::ADMIN, $admin);
    }

    /**
     * @inheritdoc
     */
    public function getTracking()
    {
        return $this->_get(self::TRACKING);
    }

    /**
     * @inheritdoc
     */
    public function setTracking($tracking)
    {
        return $this->setData(self::TRACKING, $tracking);
    }
}

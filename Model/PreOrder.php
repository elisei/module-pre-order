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

use Magento\Framework\Model\AbstractModel;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as ResourceModel;

/**
 * PreOrder Model Class
 *
 * This class handles pre-order data and operations
 */
class PreOrder extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Set Customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * Get Customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Set Quote ID
     *
     * @param int|null $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData('quote_id', $quoteId);
    }

    /**
     * Get Quote ID
     *
     * @return int|null
     */
    public function getQuoteId()
    {
        return $this->getData('quote_id');
    }

    /**
     * Set Hash
     *
     * @param string $hash
     * @return $this
     */
    public function setHash($hash)
    {
        return $this->setData('hash', $hash);
    }

    /**
     * Get Hash
     *
     * @return string|null
     */
    public function getHash()
    {
        return $this->getData('hash');
    }

    /**
     * Set Admin
     *
     * @param string $admin
     * @return $this
     */
    public function setAdmin($admin)
    {
        return $this->setData('admin', $admin);
    }

    /**
     * Get Admin
     *
     * @return string|null
     */
    public function getAdmin()
    {
        return $this->getData('admin');
    }

    /**
     * Set Tracking
     *
     * @param string $tracking
     * @return $this
     */
    public function setTracking($tracking)
    {
        return $this->setData('tracking', $tracking);
    }

    /**
     * Get Tracking
     *
     * @return string|null
     */
    public function getTracking()
    {
        return $this->getData('tracking');
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Load Pre Order by Hash
     *
     * @param string $hash
     * @return $this
     */
    public function loadByHash($hash)
    {
        $this->_getResource()->loadByHash($this, $hash);
        return $this;
    }
}

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
use O2TI\PreOrder\Api\Data\PreOrderInterface;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as ResourceModel;

/**
 * PreOrder Model Class
 *
 * This class handles pre-order data and operations
 */
class PreOrder extends AbstractModel implements PreOrderInterface
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
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
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
        return $this->getData(self::QUOTE_ID);
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
        return $this->getData(self::HASH);
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
        return $this->getData(self::ADMIN);
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
        return $this->getData(self::TRACKING);
    }

    /**
     * @inheritdoc
     */
    public function setTracking($tracking)
    {
        return $this->setData(self::TRACKING, $tracking);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
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

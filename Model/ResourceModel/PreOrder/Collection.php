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

namespace O2TI\PreOrder\Model\ResourceModel\PreOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use O2TI\PreOrder\Model\PreOrder as PreOrderModel;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as PreOrderResource;

/**
 * PreOrder Collection
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize collection
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct(): void
    {
        $this->_init(
            PreOrderModel::class,
            PreOrderResource::class
        );
    }
}

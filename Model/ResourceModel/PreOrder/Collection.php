<?php

declare(strict_types=1);

namespace O2TI\PreOrder\Model\ResourceModel\PreOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use O2TI\PreOrder\Model\PreOrder as PreOrderModel;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as PreOrderResource;

/**
 * PreOrder Collection
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
     */
    protected function _construct(): void
    {
        $this->_init(
            PreOrderModel::class,
            PreOrderResource::class
        );
    }
}

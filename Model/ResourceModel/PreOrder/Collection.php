<?php
namespace O2TI\PreOrder\Model\ResourceModel\PreOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use O2TI\PreOrder\Model\PreOrder as Model;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
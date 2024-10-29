<?php
namespace O2TI\PreOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PreOrder extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('pre_order', 'entity_id');
    }

    public function loadByHash(\O2TI\PreOrder\Model\PreOrder $model, $hash)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('hash = :hash');

        $data = $connection->fetchRow($select, ['hash' => $hash]);

        if ($data) {
            $model->setData($data);
        }

        $this->unserializeFields($model);
        $this->_afterLoad($model);

        return $model;
    }
}
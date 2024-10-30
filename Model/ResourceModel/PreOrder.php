<?php
declare(strict_types=1);

namespace O2TI\PreOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use O2TI\PreOrder\Model\PreOrder as PreOrderModel;

class PreOrder extends AbstractDb
{
    /**
     * Initialize resource model
     */
    protected function _construct(): void
    {
        $this->_init('pre_order', 'entity_id');
    }

    /**
     * Load pre-order by hash
     *
     * @param PreOrderModel $model
     * @param string $hash
     * @return PreOrderModel
     * @throws LocalizedException
     */
    public function loadByHash(PreOrderModel $model, string $hash): PreOrderModel
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

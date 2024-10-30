<?php

declare(strict_types=1);

namespace O2TI\PreOrder\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Resource Model for customer quote operations
 */
class CustomerQuoteResource extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_quote_resource';

    /**
     * Initialize table and primary key
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('quote', 'entity_id');
    }

    /**
     * Get latest non-active quote ID for customer that hasn't been ordered
     *
     * @param int $customerId Customer ID
     * @return int|null Latest quote ID or null if not found
     */
    public function getLatestQuoteIdOrNullForCustomer(int $customerId): ?int
    {
        $connection = $this->getConnection();
        $select = $this->buildLatestQuoteSelect($connection, $customerId);
        $data = $connection->fetchRow($select);

        return isset($data['entity_id']) ? (int)$data['entity_id'] : null;
    }

    /**
     * Build select query for getting latest quote
     *
     * @param AdapterInterface $connection
     * @param int $customerId
     * @return Select
     */
    private function buildLatestQuoteSelect(AdapterInterface $connection, int $customerId): Select
    {
        return $connection->select()
            ->from(
                $this->getTable('quote'),
                ['entity_id']
            )
            ->where('customer_id = ?', $customerId)
            ->where('is_active = ?', 0)
            ->where('reserved_order_id IS NULL')
            ->order('updated_at ' . Select::SQL_DESC)
            ->limit(1);
    }
}

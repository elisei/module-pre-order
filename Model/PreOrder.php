<?php
namespace O2TI\PreOrder\Model;

use Magento\Framework\Model\AbstractModel;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as ResourceModel;

class PreOrder extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    public function setQuoteId($quoteId)
    {
        return $this->setData('quote_id', $quoteId);
    }

    public function getQuoteId()
    {
        return $this->getData('quote_id');
    }

    public function setHash($hash)
    {
        return $this->setData('hash', $hash);
    }

    public function getHash()
    {
        return $this->getData('hash');
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    public function setExpiratedAt($expiratedAt)
    {
        return $this->setData('expirated_at', $expiratedAt);
    }

    public function getExpiratedAt()
    {
        return $this->getData('expirated_at');
    }

    public function loadByHash($hash)
    {
        $this->_getResource()->loadByHash($this, $hash);
        return $this;
    }
}
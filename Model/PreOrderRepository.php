<?php
namespace O2TI\PreOrder\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use O2TI\PreOrder\Model\PreOrder;
use O2TI\PreOrder\Model\PreOrderFactory;
use O2TI\PreOrder\Model\ResourceModel\PreOrder as ResourceModel;

class PreOrderRepository implements \O2TI\PreOrder\Api\PreOrderRepositoryInterface
{
    private $preOrderFactory;
    private $resourceModel;

    public function __construct(
        PreOrderFactory $preOrderFactory,
        ResourceModel $resourceModel
    ) {
        $this->preOrderFactory = $preOrderFactory;
        $this->resourceModel = $resourceModel;
    }

    public function save(PreOrder $preOrder)
    {
        try {
            $this->resourceModel->save($preOrder);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $preOrder;
    }

    public function getById($id)
    {
        $preOrder = $this->preOrderFactory->create();
        $this->resourceModel->load($preOrder, $id);
        if (!$preOrder->getId()) {
            throw new NoSuchEntityException(__('The pre-order with the "%1" ID doesn\'t exist.', $id));
        }
        return $preOrder;
    }

    public function delete(PreOrder $preOrder)
    {
        try {
            $this->resourceModel->delete($preOrder);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    public function getByHash($hash)
    {
        $preOrder = $this->preOrderFactory->create();
        $this->resourceModel->loadByHash($preOrder, $hash);
        if (!$preOrder->getQuoteId()) {
            throw new NoSuchEntityException(__('The pre-order with the hash "%1" doesn\'t exist.', $hash));
        }
        return $preOrder;
    }
}
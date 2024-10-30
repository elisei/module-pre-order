<?php
declare(strict_types=1);

namespace O2TI\PreOrder\Ui\Component\Listing;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as MageDataProvider;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use O2TI\PreOrder\Model\ResourceModel\PreOrder\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class DataProvider extends MageDataProvider
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var array
     */
    protected $emailFilters = [];

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param CollectionFactory $collectionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CustomerRepositoryInterface $customerRepository,
        CollectionFactory $collectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->customerRepository = $customerRepository;
        $this->collectionFactory = $collectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $collection = $this->getCollection();

        $criteria = $this->searchCriteriaBuilder->create();
        $this->applyPagination($criteria);
        
        $items = [];
        foreach ($collection as $item) {
            $itemData = $item->getData();
            try {
                if (!empty($itemData['customer_id'])) {
                    $customer = $this->customerRepository->getById($itemData['customer_id']);
                    $itemData['customer_email'] = $customer->getEmail();
                } else {
                    $itemData['customer_email'] = __('Guest');
                }
            } catch (\Exception $e) {
                $itemData['customer_email'] = __('N/A');
            }
            
            $items[] = $itemData;
        }
        
        $this->loadedData = [
            'items' => $items,
            'totalRecords' => $collection->getSize()
        ];
        
        return $this->loadedData;
    }

    /**
     * Get collection
     *
     * @return \Magento\Framework\Data\Collection
     */
    protected function getCollection()
    {
        $collection = $this->collectionFactory->create();

        if (!empty($this->emailFilters)) {
            $customerIds = $this->getCustomerIdsByEmail($this->emailFilters);
            if (!empty($customerIds)) {
                $collection->addFieldToFilter('customer_id', ['in' => $customerIds]);
            } else {
                // Se nenhum cliente for encontrado com o email, força retorno vazio
                $collection->addFieldToFilter('entity_id', ['eq' => 0]);
            }
        }

        foreach ($this->filterBuilder->getData() as $filter) {
            if ($filter->getField() === 'customer_email') {
                continue;
            }
            $collection->addFieldToFilter(
                $filter->getField(),
                [$filter->getConditionType() => $filter->getValue()]
            );
        }
        
        return $collection;
    }

    /**
     * Get customer IDs by email
     *
     * @param array $filters
     * @return array
     */
    protected function getCustomerIdsByEmail($filters)
    {
        $customerCollection = $this->customerCollectionFactory->create();
        
        foreach ($filters as $filter) {
            $condition = $filter['condition'];
            $value = $filter['value'];

            switch ($condition) {
                case 'like':
                    $value = "%$value%";
                    break;
                case 'eq':
                    break;
            }
            
            $customerCollection->addFieldToFilter('email', [$condition => $value]);
        }
        
        return $customerCollection->getAllIds();
    }

    /**
     * Apply pagination to collection
     *
     * @param SearchCriteriaInterface $criteria
     * @return void
     */
    protected function applyPagination(SearchCriteriaInterface $criteria)
    {
        $paging = $criteria->getPageSize();
        $curPage = $criteria->getCurrentPage();
        
        if ($paging) {
            $this->getCollection()->setPageSize($paging);
        }
        if ($curPage) {
            $this->getCollection()->setCurPage($curPage);
        }
    }

    /**
     * Add full text filter
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        if ($filter->getField() === 'customer_email') {
            $this->emailFilters[] = [
                'condition' => $filter->getConditionType(),
                'value' => $filter->getValue()
            ];
            return;
        }
        
        parent::addFilter($filter);
    }
}
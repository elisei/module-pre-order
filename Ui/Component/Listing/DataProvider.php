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
use O2TI\PreOrder\Model\ResourceModel\PreOrder\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Api\Search\DocumentFactory;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\Search\DocumentInterface;

class DataProvider extends MageDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var DocumentFactory
     */
    protected $documentFactory;

    /**
     * @var SearchResultFactory
     */
    protected $searchResultFactory;

    /**
     * @var AttributeValueFactory
     */
    protected $attributeValueFactory;

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
     * @param CollectionFactory $collectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param DocumentFactory $documentFactory
     * @param SearchResultFactory $searchResultFactory
     * @param AttributeValueFactory $attributeValueFactory
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
        CollectionFactory $collectionFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        DocumentFactory $documentFactory,
        SearchResultFactory $searchResultFactory,
        AttributeValueFactory $attributeValueFactory,
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
        $this->collectionFactory = $collectionFactory;
        $this->customerRepository = $customerRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->documentFactory = $documentFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->attributeValueFactory = $attributeValueFactory;
    }

    /**
     * @inheritDoc
     */
    public function getSearchResult()
    {
        /** @var \Magento\Framework\Api\Search\SearchCriteria $searchCriteria */
        $searchCriteria = $this->getSearchCriteria();
        
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->collectionFactory->create();
        
        // Aplica os filtros padrão
        foreach ($this->filterBuilder->getData() as $filter) {
            if ($filter->getField() === 'customer_email') {
                continue;
            }
            $collection->addFieldToFilter(
                $filter->getField(),
                [$filter->getConditionType() => $filter->getValue()]
            );
        }

        // Aplica filtro de email se existir
        if (!empty($this->emailFilters)) {
            $customerIds = $this->getCustomerIdsByEmail($this->emailFilters);
            if (!empty($customerIds)) {
                $collection->addFieldToFilter('customer_id', ['in' => $customerIds]);
            } else {
                $collection->addFieldToFilter('entity_id', ['eq' => 0]);
            }
        }

        // Aplica ordenação
        if ($this->request->getParam('sorting')) {
            $sorting = $this->request->getParam('sorting');
            if (isset($sorting['field']) && !empty($sorting['field'])) {
                $direction = $sorting['direction'] ?? 'DESC';
                $collection->addOrder($sorting['field'], $direction);
            } else {
                $collection->addOrder('entity_id', 'DESC');
            }
        } else {
            $collection->addOrder('entity_id', 'DESC');
        }

        // Aplica paginação
        $pageSize = $this->request->getParam('paging')['pageSize'] ?? 20;
        $currentPage = $this->request->getParam('paging')['current'] ?? 1;
        
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }
        if ($currentPage) {
            $collection->setCurPage($currentPage);
        }

        // Converte os items para documentos
        $searchDocuments = [];
        foreach ($collection->getItems() as $item) {
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

            /** @var DocumentInterface $document */
            $document = $this->documentFactory->create();
            foreach ($itemData as $key => $value) {
                $attributeValue = $this->attributeValueFactory->create();
                $attributeValue->setAttributeCode($key);
                $attributeValue->setValue($value);
                $document->setCustomAttribute($key, $attributeValue);
            }
            $document->setId($itemData['entity_id']);
            
            $searchDocuments[] = $document;
        }

        // Cria o resultado da pesquisa
        $searchResult = $this->searchResultFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setItems($searchDocuments);

        return $searchResult;
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
     * @inheritDoc
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

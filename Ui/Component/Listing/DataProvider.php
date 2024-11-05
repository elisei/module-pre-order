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

namespace O2TI\PreOrder\Ui\Component\Listing;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as MageDataProvider;
use Magento\Customer\Api\CustomerRepositoryInterface;
use O2TI\PreOrder\Model\ResourceModel\PreOrder\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustCollFactory;
use Magento\Framework\Api\Search\DocumentFactory;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Data Provider for PreOrder Grid
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends MageDataProvider
{
    private const DEFAULT_PAGE_SIZE = 20;
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_SORT_FIELD = 'entity_id';
    private const DEFAULT_SORT_DIRECTION = 'DESC';
    
    /**
     * @var array
     */
    private array $numericFields = ['entity_id', 'customer_id', 'quote_id'];

    /**
     * @var array
     */
    private array $specialFields = ['customer_email', 'customer_id'];

    /**
     * @var CollectionFactory
     */
    private $preOrders;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customers;

    /**
     * @var CustCollFactory
     */
    private $custCollection;

    /**
     * @var DocumentFactory
     */
    private $documents;

    /**
     * @var SearchResultFactory
     */
    private $searchResult;

    /**
     * @var AttributeValueFactory
     */
    private $attrValues;

    /**
     * @var array
     */
    private array $emailFilters = [];

    /**
     * @var array
     */
    private array $filters = [];

    /**
     * @var array
     */
    private array $customerFilters = [];

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param CollectionFactory $preOrders
     * @param CustomerRepositoryInterface $customers
     * @param CustCollFactory $custCollection
     * @param DocumentFactory $documents
     * @param SearchResultFactory $searchResult
     * @param AttributeValueFactory $attrValues
     * @param array $meta
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CollectionFactory $preOrders,
        CustomerRepositoryInterface $customers,
        CustCollFactory $custCollection,
        DocumentFactory $documents,
        SearchResultFactory $searchResult,
        AttributeValueFactory $attrValues,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->preOrders = $preOrders;
        $this->customers = $customers;
        $this->custCollection = $custCollection;
        $this->documents = $documents;
        $this->searchResult = $searchResult;
        $this->attrValues = $attrValues;
    }

    /**
     * Get search results
     *
     * @return \Magento\Framework\Api\Search\SearchResultInterface
     */
    public function getSearchResult()
    {
        $collection = $this->preOrders->create();
        
        $this->applyFilters($collection);
        $this->applyCustomerFilters($collection);
        $this->applyEmailFilter($collection);
        $this->applySorting($collection);
        $this->applyPagination($collection);
        
        $documents = $this->createSearchDocuments($collection);
        
        return $this->createSearchResult($documents, $collection->getSize());
    }

    /**
     * Apply filters to collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     */
    private function applyFilters($collection): void
    {
        foreach ($this->filters as $filter) {
            if (in_array($filter->getField(), $this->specialFields, true)) {
                continue;
            }
            $this->addFilterToCollection($collection, $filter);
        }
    }

    /**
     * Apply customer filters to collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     */
    private function applyCustomerFilters($collection): void
    {
        if (empty($this->customerFilters)) {
            return;
        }

        foreach ($this->customerFilters as $filter) {
            $this->addFilterToCollection($collection, $filter);
        }
    }

    /**
     * Add filter to collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @param Filter $filter
     * @return void
     */
    private function addFilterToCollection($collection, Filter $filter): void
    {
        try {
            $field = $filter->getField();
            $condition = $filter->getConditionType();
            $value = $filter->getValue();
            
            // Handle special cases for number fields
            if (in_array($field, $this->numericFields, true)) {
                if ($condition === 'like') {
                    $condition = 'eq';
                    $value = str_replace(['%', '_'], '', $value);
                }
                $value = (int)$value;
            }

            $collection->addFieldToFilter($field, [$condition => $value]);
        } catch (LocalizedException $e) {
            // Log error or handle exception as needed
        }
    }

    /**
     * Apply email filter to collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function applyEmailFilter($collection): void
    {
        if (empty($this->emailFilters)) {
            return;
        }

        $customerIds = $this->getCustomerIdsByEmail($this->emailFilters);
        if (!empty($customerIds)) {
            $collection->addFieldToFilter(
                'customer_id',
                ['in' => $customerIds]
            );
        } else {
            $collection->addFieldToFilter(
                'customer_id',
                ['null' => true]
            );
        }
    }

    /**
     * Apply sorting to collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     */
    private function applySorting($collection): void
    {
        $sorting = $this->request->getParam('sorting', []);
        $field = $sorting['field'] ?? self::DEFAULT_SORT_FIELD;
        $direction = $sorting['direction'] ?? self::DEFAULT_SORT_DIRECTION;
        
        $collection->addOrder($field, $direction);
    }

    /**
     * Apply pagination to collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     */
    private function applyPagination($collection): void
    {
        $paging = $this->request->getParam('paging', []);
        $pageSize = $paging['pageSize'] ?? self::DEFAULT_PAGE_SIZE;
        $currentPage = $paging['current'] ?? self::DEFAULT_PAGE;
        
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);
    }

    /**
     * Create search documents from collection items
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return array
     */
    private function createSearchDocuments($collection): array
    {
        $documents = [];
        foreach ($collection->getItems() as $item) {
            $itemData = $this->prepareItemData($item);
            $documents[] = $this->createDocument($itemData);
        }
        return $documents;
    }

    /**
     * Prepare item data with customer email
     *
     * @param \Magento\Framework\Model\AbstractModel $item
     * @return array
     */
    private function prepareItemData($item): array
    {
        $itemData = $item->getData();
        $customerId = isset($itemData['customer_id']) ? (int)$itemData['customer_id'] : null;
        $itemData['customer_email'] = $this->getCustomerEmail($customerId);
        return $itemData;
    }

    /**
     * Get customer email by ID
     *
     * @param int|null $customerId
     * @return string
     *
     */
    private function getCustomerEmail(?int $customerId): string
    {
        if (empty($customerId)) {
            return (string)__('Guest');
        }

        try {
            return $this->customers->getById($customerId)->getEmail();
        } catch (\Exception $e) {
            return (string)__('N/A');
        }
    }

    /**
     * Create document from item data
     *
     * @param array $itemData
     * @return DocumentInterface
     */
    private function createDocument(array $itemData): DocumentInterface
    {
        $document = $this->documents->create();
        foreach ($itemData as $key => $value) {
            $attribute = $this->attrValues->create();
            $attribute->setAttributeCode($key);
            $attribute->setValue($value);
            $document->setCustomAttribute($key, $attribute);
        }
        $document->setId($itemData['entity_id']);
        return $document;
    }

    /**
     * Create search result
     *
     * @param array $documents
     * @param int $totalCount
     * @return \Magento\Framework\Api\Search\SearchResultInterface
     */
    private function createSearchResult(array $documents, int $totalCount)
    {
        $searchResult = $this->searchResult->create();
        $searchResult->setSearchCriteria($this->getSearchCriteria());
        $searchResult->setTotalCount($totalCount);
        $searchResult->setItems($documents);
        return $searchResult;
    }

    /**
     * Get customer IDs by email
     *
     * @param array $filters
     * @return array
     */
    protected function getCustomerIdsByEmail(array $filters): array
    {
        $collection = $this->custCollection->create();
        
        foreach ($filters as $filter) {
            $value = $this->prepareEmailFilterValue($filter);
            $collection->addFieldToFilter('email', [$filter['condition'] => $value]);
        }
        
        return $collection->getAllIds();
    }

    /**
     * Prepare email filter value
     *
     * @param array $filter
     * @return string
     */
    private function prepareEmailFilterValue(array $filter): string
    {
        return $filter['condition'] === 'like' ? "%{$filter['value']}%" : $filter['value'];
    }

    /**
     * Add filter
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter): void
    {
        $field = $filter->getField();
        
        if ($field === 'customer_email') {
            $this->emailFilters[] = [
                'condition' => $filter->getConditionType(),
                'value' => $filter->getValue()
            ];
            return;
        }

        if ($field === 'customer_id') {
            $this->customerFilters[] = $filter;
            return;
        }
        
        $this->filters[] = $filter;
    }
}
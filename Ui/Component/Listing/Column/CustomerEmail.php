<?php
declare(strict_types=1);

namespace O2TI\PreOrder\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerEmail extends Column
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerRepositoryInterface $customerRepository,
        array $components = [],
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $customerId = $item['customer_id'] ?? null;
                try {
                    if ($customerId) {
                        $customer = $this->customerRepository->getById((int)$customerId);
                        $item[$this->getData('name')] = $customer->getEmail();
                    } else {
                        $item[$this->getData('name')] = __('Guest');
                    }
                } catch (\Exception $e) {
                    $item[$this->getData('name')] = __('N/A');
                }
            }
        }

        return $dataSource;
    }
}
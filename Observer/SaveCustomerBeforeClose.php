<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Observer;

use Magento\Backend\Model\Session\Quote;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

/**
 * Observer for saving customer before closing quote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveCustomerBeforeClose implements ObserverInterface
{
    /**
     * Configuration path for force account creation
     */
    public const XML_PATH_FORCE_CREATE_ACCOUNT = 'preorder/general/force_create_account';

    /**
     * @var Create
     */
    private $adminOrderCreate;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var FormFactory
     */
    private $metadataFormFactory;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var Mapper
     */
    private $customerMapper;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * SaveCustomerBeforeClose constructor
     *
     * @param Create $adminOrderCreate
     * @param CustomerRepository $customerRepository
     * @param FormFactory $metadataFormFactory
     * @param AccountManagementInterface $accountManagement
     * @param Mapper $customerMapper
     * @param DataObjectHelper $dataObjectHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Create $adminOrderCreate,
        CustomerRepository $customerRepository,
        FormFactory $metadataFormFactory,
        AccountManagementInterface $accountManagement,
        Mapper $customerMapper,
        DataObjectHelper $dataObjectHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->adminOrderCreate = $adminOrderCreate;
        $this->customerRepository = $customerRepository;
        $this->metadataFormFactory = $metadataFormFactory;
        $this->accountManagement = $accountManagement;
        $this->customerMapper = $customerMapper;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $quoteSession */
        $quoteSession = $observer->getData('session');
        $store = $quoteSession->getStore();

        if (!$this->isForceCreateAccountEnabled($store->getId())) {
            return;
        }

        $quote = $quoteSession->getQuote();
        if ($quote->getCustomer()->getId()) {
            return;
        }

        /** @var RequestInterface $request */
        $request = $observer->getData('request_model');
        
        $email = $this->getEmailFromRequest($request);
        if (!$email) {
            return;
        }

        $this->createCustomerAccount($quote, $email, $store);
    }

    /**
     * Check if force account creation is enabled
     *
     * @param int $storeId
     * @return bool
     */
    private function isForceCreateAccountEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FORCE_CREATE_ACCOUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Create customer account from quote data
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $email
     * @param \Magento\Store\Model\Store $store
     * @return void
     * @throws LocalizedException
     */
    private function createCustomerAccount($quote, string $email, $store): void
    {
        /** @var CustomerInterface $customer */
        $customer = $quote->getCustomer();
        $customerGroupId = $quote->getCustomerGroupId();

        $cusAddressDataObject = $this->adminOrderCreate->getShippingAddress()->exportCustomerAddress();

        $customer->setSuffix($cusAddressDataObject->getSuffix())
            ->setFirstname($cusAddressDataObject->getFirstname())
            ->setLastname($cusAddressDataObject->getLastname())
            ->setMiddlename($cusAddressDataObject->getMiddlename())
            ->setPrefix($cusAddressDataObject->getPrefix())
            ->setStoreId($store->getId())
            ->setGroupId($customerGroupId)
            ->setWebsiteId($store->getWebsiteId())
            ->setEmail($email);

        $customer = $this->validateCustomerData($customer);
        $customer = $this->customerRepository->save($customer);
        $quote->setCustomer($customer);
    }

    /**
     * Get email from request
     *
     * @param RequestInterface $request
     * @return string|null
     */
    private function getEmailFromRequest(RequestInterface $request): ?string
    {
        $order = $request->getParam('order');
        return isset($order['account']['email']) ? $order['account']['email'] : null;
    }

    /**
     * Validate customer data
     *
     * @param CustomerInterface $customer
     * @return CustomerInterface
     * @throws LocalizedException
     */
    private function validateCustomerData(CustomerInterface $customer): CustomerInterface
    {
        $customerForm = $this->metadataFormFactory->create(
            \Magento\Customer\Api\CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'adminhtml_checkout',
            $this->customerMapper->toFlatArray($customer),
            false,
            \Magento\Customer\Model\Metadata\Form::DONT_IGNORE_INVISIBLE
        );

        $requestData = ['order' => $this->adminOrderCreate->getData()];
        $request = $customerForm->prepareRequest($requestData);
        $data = $customerForm->extractData($request, 'order/account');

        $validationResults = $this->accountManagement->validate($customer);
        if (!$validationResults->isValid()) {
            $errors = $validationResults->getMessages();
            if (is_array($errors)) {
                throw new LocalizedException(__(implode(PHP_EOL, $errors)));
            }
        }

        $data = $customerForm->restoreData($data);
        foreach ($data as $key => $value) {
            if ($value !== null) {
                unset($data[$key]);
            }
        }

        $this->dataObjectHelper->populateWithArray(
            $customer,
            $data,
            \Magento\Customer\Api\Data\CustomerInterface::class
        );

        return $customer;
    }
}

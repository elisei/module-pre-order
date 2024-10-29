<?php

namespace O2TI\PreOrder\Observer;

use Magento\Backend\Model\Session\Quote;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class SaveCustomerBeforeClose
 */
class SaveCustomerBeforeClose implements ObserverInterface
{
    /** @var Create */
    private $adminOrderCreate;

    /** @var CustomerRepository */
    private $customerRepository;

    /** @var FormFactory */
    private $metadataFormFactory;

    /** @var AccountManagementInterface */
    private $accountManagement;

    /** @var Mapper */
    private $customerMapper;

    /** @var DataObjectHelper */
    private $dataObjectHelper;

    /**
     * SaveCustomerBeforeClose constructor.
     *
     * @param Create $adminOrderCreate
     * @param CustomerRepository $customerRepository
     * @param FormFactory $metadataFormFactory
     * @param AccountManagementInterface $accountManagement
     * @param Mapper $customerMapper
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        Create $adminOrderCreate,
        CustomerRepository $customerRepository,
        FormFactory $metadataFormFactory,
        AccountManagementInterface $accountManagement,
        Mapper $customerMapper,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->adminOrderCreate = $adminOrderCreate;
        $this->customerRepository = $customerRepository;
        $this->metadataFormFactory = $metadataFormFactory;
        $this->accountManagement = $accountManagement;
        $this->customerMapper = $customerMapper;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request_model');

        /** @var Quote $quoteSession */
        $quoteSession = $observer->getData('session');

        $store = $quoteSession->getStore();

        $quote = $quoteSession->getQuote();
        if ($quote->getCustomer()->getId()) {
            return;
        }

        $email = $this->getEmailFromRequest($request);
        if (!$email) {
            return;
        }

        /** @var CustomerInterface $customer */
        $customer = $quote->getCustomer();
        $customerGroupId = $quote->getCustomerGroupId();

        $customerShippingAddressDataObject = $this->adminOrderCreate->getShippingAddress()->exportCustomerAddress();

        $customer->setSuffix($customerShippingAddressDataObject->getSuffix())
            ->setFirstname($customerShippingAddressDataObject->getFirstname())
            ->setLastname($customerShippingAddressDataObject->getLastname())
            ->setMiddlename($customerShippingAddressDataObject->getMiddlename())
            ->setPrefix($customerShippingAddressDataObject->getPrefix())
            ->setStoreId($store->getId())
            ->setGroupId($customerGroupId)
            ->setWebsiteId($store->getWebsiteId())
            ->setEmail($email);

        // Validate customer data before saving
        $customer = $this->validateCustomerData($customer);

        $customer = $this->customerRepository->save($customer);
        $quote->setCustomer($customer);
    }

    /**
     * Function: getEmailFromRequest
     *
     * @param RequestInterface $request
     * @return string|null
     */
    private function getEmailFromRequest($request)
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
    private function validateCustomerData(CustomerInterface $customer)
    {
        $customerForm = $this->metadataFormFactory->create(
            \Magento\Customer\Api\CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'adminhtml_checkout',
            $this->customerMapper->toFlatArray($customer),
            false,
            \Magento\Customer\Model\Metadata\Form::DONT_IGNORE_INVISIBLE
        );

        // Prepare request data
        $requestData = ['order' => $this->adminOrderCreate->getData()];
        $request = $customerForm->prepareRequest($requestData);
        $data = $customerForm->extractData($request, 'order/account');

        // Validate customer data
        $validationResults = $this->accountManagement->validate($customer);
        if (!$validationResults->isValid()) {
            $errors = $validationResults->getMessages();
            if (is_array($errors)) {
                throw new LocalizedException(__(implode(PHP_EOL, $errors)));
            }
        }

        // Restore and filter data
        $data = $customerForm->restoreData($data);
        foreach ($data as $key => $value) {
            if ($value !== null) {
                unset($data[$key]);
            }
        }

        // Update customer with validated data
        $this->dataObjectHelper->populateWithArray(
            $customer,
            $data,
            \Magento\Customer\Api\Data\CustomerInterface::class
        );

        return $customer;
    }
}
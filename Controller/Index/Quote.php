<?php

declare(strict_types=1);

namespace O2TI\PreOrder\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use O2TI\PreOrder\Api\PreOrderRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote as MageQuote;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Class Quote
 * Controller for managing pre-order quote operations
 */
class Quote extends Action implements CsrfAwareActionInterface
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * @var QuoteFactory
     */
    protected QuoteFactory $quoteFactory;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $quoteRepository;

    /**
     * @var Cart
     */
    protected Cart $cart;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $eventManager;

    /**
     * @var PreOrderRepositoryInterface
     */
    protected PreOrderRepositoryInterface $preOrderRepository;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param QuoteFactory $quoteFactory
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param Cart $cart
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param ManagerInterface $eventManager
     * @param PreOrderRepositoryInterface $preOrderRepository
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QuoteFactory $quoteFactory,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        Cart $cart,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        ManagerInterface $eventManager,
        PreOrderRepositoryInterface $preOrderRepository,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->cart = $cart;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->eventManager = $eventManager;
        $this->preOrderRepository = $preOrderRepository;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Create Csrf Validation Exception.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        if ($request) {
            return null;
        }
    }

    /**
     * Validate For Csrf.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        if ($request) {
            return true;
        }
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $hash = $this->getRequest()->getParam('hash');
        $errorMessages = [];

        // Limpa sessão do checkout
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->clearStorage();
        $this->checkoutSession->restoreQuote();
        
        // Limpa carrinho
        $this->cart->truncate()->save();
        
        try {
            $oldQuote = $this->loadPreOrderAndQuote($hash, $errorMessages);
            $this->deactivateExistingQuotes($errorMessages);
            $this->handleCustomerLogin($oldQuote);
            
            $newQuote = $this->createNewQuote($oldQuote, $errorMessages);
            $this->setupQuoteSession($newQuote, $errorMessages);

            $this->handleSuccessMessages($errorMessages);
            return $this->redirectToCart();

        } catch (\Exception $e) {
            return $this->handleExecutionError($e, $errorMessages);
        }
    }

    /**
     * Load PreOrder and associated Quote
     *
     * @param string $hash
     * @param array $errorMessages
     * @return MageQuote
     * @throws NoSuchEntityException
     */
    protected function loadPreOrderAndQuote(string $hash, array &$errorMessages): MageQuote
    {
        try {
            $preOrder = $this->preOrderRepository->getByHash($hash);
            $oldQuoteId = $preOrder->getQuoteId();
            $oldQuote = $this->quoteFactory->create()->load($oldQuoteId);

            if (!$oldQuote->getId()) {
                throw new NoSuchEntityException(__('Cotação não encontrada.'));
            }

            return $oldQuote;
        } catch (\Exception $e) {
            $errorMessages[] = 'Erro ao carregar PreOrder/Cotação: ' . $e->getMessage();
            throw $e;
        }
    }

    /**
     * Deactivate existing quotes in session
     *
     * @param array $errorMessages
     * @return void
     */
    protected function deactivateExistingQuotes(array &$errorMessages): void
    {
        try {
            if ($this->checkoutSession->getQuoteId()) {
                $currentQuote = $this->quoteRepository->get($this->checkoutSession->getQuoteId());
                if ($currentQuote->getId()) {
                    $currentQuote->setIsActive(0);
                    $this->quoteRepository->save($currentQuote);
                }
            }
        } catch (\Exception $e) {
            $errorMessages[] = 'Erro ao desativar quote existente: ' . $e->getMessage();
        }
    }

    /**
     * Handle customer login if needed
     *
     * @param MageQuote $oldQuote
     * @return void
     * @throws LocalizedException
     */
    protected function handleCustomerLogin(MageQuote $oldQuote): void
    {
        try {
            if ($oldQuote->getCustomerId()) {
                $customer = $this->customerRepository->getById($oldQuote->getCustomerId());
                $this->customerSession->setCustomerDataAsLoggedIn($customer);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Erro ao realizar login do cliente: %1', $e->getMessage()));
        }
    }

    /**
     * Create new quote based on old quote
     *
     * @param MageQuote $oldQuote
     * @param array $errorMessages
     * @return MageQuote
     * @throws LocalizedException
     */
    protected function createNewQuote(MageQuote $oldQuote, array &$errorMessages): MageQuote
    {
        try {
            $newQuote = $this->initializeNewQuote($oldQuote);
            $this->cloneCustomerData($oldQuote, $newQuote);
            $this->cloneItems($oldQuote, $newQuote);
            $this->cloneAddressesAndShipping($oldQuote, $newQuote);
            
            $this->finalizeNewQuote($newQuote);
            
            return $newQuote;
        } catch (\Exception $e) {
            $errorMessages[] = 'Erro ao criar nova quote: ' . $e->getMessage();
            throw new LocalizedException(__('Erro ao criar nova quote: %1', $e->getMessage()));
        }
    }

    /**
     * Initialize new quote with base properties
     *
     * @param MageQuote $oldQuote
     * @return MageQuote
     */
    protected function initializeNewQuote(MageQuote $oldQuote): MageQuote
    {
        $newQuote = clone $oldQuote;
        $newQuote->setId(null)
            ->setIsActive(1)
            ->setReservedOrderId(null)
            ->setUpdatedAt(null)
            ->setCreatedAt(null);
        
        return $newQuote;
    }

    /**
     * Finalize new quote preparation
     *
     * @param MageQuote $newQuote
     * @return void
     */
    protected function finalizeNewQuote(MageQuote $newQuote): void
    {
        $newQuote->collectTotals();
        if (!$newQuote->getAllItems()) {
            // Força recálculo de taxas e totais
            $newQuote->setTriggerRecollect(1);
            $newQuote->collectTotals();

            $this->quoteRepository->save($newQuote);
        }
    }

    /**
     * Clone customer data from old quote to new quote
     *
     * @param MageQuote $oldQuote
     * @param MageQuote $newQuote
     * @return void
     * @throws LocalizedException
     */
    protected function cloneCustomerData(MageQuote $oldQuote, MageQuote $newQuote): void
    {
        try {
            $newQuote->setStoreId($oldQuote->getStoreId())
                ->setCustomerId($oldQuote->getCustomerId())
                ->setCustomerEmail($oldQuote->getCustomerEmail())
                ->setCustomerGroupId($oldQuote->getCustomerGroupId())
                ->setCustomerTaxClassId($oldQuote->getCustomerTaxClassId())
                ->setCustomerFirstname($oldQuote->getCustomerFirstname())
                ->setCustomerLastname($oldQuote->getCustomerLastname())
                ->setCustomerMiddlename($oldQuote->getCustomerMiddlename())
                ->setCustomerPrefix($oldQuote->getCustomerPrefix())
                ->setCustomerSuffix($oldQuote->getCustomerSuffix())
                ->setCustomerDob($oldQuote->getCustomerDob())
                ->setCustomerTaxvat($oldQuote->getCustomerTaxvat())
                ->setCustomerGender($oldQuote->getCustomerGender())
                ->setCustomerIsGuest($oldQuote->getCustomerIsGuest())
                ->setCustomerNote($oldQuote->getCustomerNote())
                ->setCustomerNoteNotify($oldQuote->getCustomerNoteNotify());
        } catch (\Exception $e) {
            throw new LocalizedException(__('Erro ao clonar dados do cliente: %1', $e->getMessage()));
        }
    }

    /**
     * Clone items from old quote to new quote
     *
     * @param MageQuote $oldQuote
     * @param MageQuote $newQuote
     * @return void
     * @throws LocalizedException
     */
    protected function cloneItems(MageQuote $oldQuote, MageQuote $newQuote): void
    {
        try {
            foreach ($oldQuote->getAllItems() as $item) {
                $this->cloneQuoteItem($item, $newQuote);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Erro ao clonar itens do carrinho: %1', $e->getMessage()));
        }
    }

    /**
     * Clone individual quote item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param MageQuote $newQuote
     * @return void
     * @throws LocalizedException
     */
    protected function cloneQuoteItem($item, MageQuote $newQuote): void
    {
        try {
            $newItem = clone $item;
            $newItem->setId(null)
                ->setQuote($newQuote)
                ->setCreatedAt(null)
                ->setUpdatedAt(null)
                ->setParentItemId(null)
                ->setQuoteId(null);

            if ($item->getParentItem()) {
                $newItem->setParentItem($newQuote->getItemByProduct($item->getParentItem()->getProduct()));
            }

            $newQuote->addItem($newItem);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Erro ao clonar item %1: %2', $item->getName(), $e->getMessage()));
        }
    }

    /**
     * Clone addresses and shipping method from old quote to new quote
     *
     * @param MageQuote $oldQuote
     * @param MageQuote $newQuote
     * @return void
     * @throws LocalizedException
     */
    protected function cloneAddressesAndShipping(MageQuote $oldQuote, MageQuote $newQuote): void
    {
        try {
            $this->cloneBillingAddress($oldQuote, $newQuote);
            $this->cloneShippingAddress($oldQuote, $newQuote);
            $this->updateShippingRates($newQuote);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Erro ao clonar endereços e método de entrega: %1', $e->getMessage()));
        }
    }

    /**
     * Clone billing address from old quote to new quote
     *
     * @param MageQuote $oldQuote
     * @param MageQuote $newQuote
     * @return void
     */
    protected function cloneBillingAddress(MageQuote $oldQuote, MageQuote $newQuote): void
    {
        $oldBillingAddress = $oldQuote->getBillingAddress();
        $newBillingAddress = clone $oldBillingAddress;
        $newBillingAddress->setId(null)
            ->setQuoteId(null)
            ->setCustomerAddressId($oldBillingAddress->getCustomerAddressId());
        $newQuote->setBillingAddress($newBillingAddress);
    }

    /**
     * Clone shipping address from old quote to new quote
     *
     * @param MageQuote $oldQuote
     * @param MageQuote $newQuote
     * @return void
     */
    protected function cloneShippingAddress(MageQuote $oldQuote, MageQuote $newQuote): void
    {
        $oldShippingAddress = $oldQuote->getShippingAddress();
        $newShippingAddress = clone $oldShippingAddress;

        $newShippingAddress->setId(null)
            ->setQuoteId(null)
            ->setAddressId(null)
            ->setCustomerAddressId($oldShippingAddress->getCustomerAddressId());

        $newShippingAddress->setShippingMethod(null)
            ->setShippingDescription(null)
            ->setShippingAmount(0)
            ->setBaseShippingAmount(0)
            ->setShippingTaxAmount(0)
            ->setBaseShippingTaxAmount(0);

        $newQuote->setShippingAddress($newShippingAddress);

        if ($oldShippingAddress->getShippingMethod()) {
            $this->configureShippingMethod(
                $newQuote,
                $oldShippingAddress->getShippingMethod(),
                $oldShippingAddress->getShippingDescription()
            );
        }
    }

    /**
     * Configure shipping method for quote
     *
     * @param MageQuote $quote
     * @param string $shippingMethod
     * @param string|null $shippingDescription
     * @return void
     */
    protected function configureShippingMethod(
        MageQuote $quote,
        string $shippingMethod,
        ?string $shippingDescription = null
    ): void {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates();
        $shippingAddress->setShippingMethod($shippingMethod);
        
        if ($shippingDescription) {
            $shippingAddress->setShippingDescription($shippingDescription);
        }

        $quote->collectTotals();
    }

    /**
     * Update shipping rates for new quote
     *
     * @param MageQuote $newQuote
     * @return void
     */
    protected function updateShippingRates(MageQuote $newQuote): void
    {
        $shippingAddress = $newQuote->getShippingAddress();

        $shippingAddress->setCollectShippingRates(true)
            ->setShippingAmount(0)
            ->setBaseShippingAmount(0)
            ->setShippingTaxAmount(0)
            ->setBaseShippingTaxAmount(0);

        $shippingAddress->collectShippingRates();

        if ($shippingMethod = $shippingAddress->getShippingMethod()) {
            $this->configureShippingMethod(
                $newQuote,
                $shippingMethod,
                $shippingAddress->getShippingDescription()
            );
        }
    }

    /**
     * Setup quote session and cart
     *
     * @param MageQuote $newQuote
     * @param array $errorMessages
     * @return void
     */
    protected function setupQuoteSession(MageQuote $newQuote, array &$errorMessages): void
    {
        try {
            $this->checkoutSession->setQuoteId($newQuote->getId());
            $this->checkoutSession->replaceQuote($newQuote);

            $this->cart->setQuote($newQuote);
            $this->cart->save();

            $this->eventManager->dispatch(
                'checkout_cart_save_after',
                ['cart' => $this->cart, 'quote' => $newQuote]
            );

        } catch (\Exception $e) {
            $errorMessages[] = 'Erro ao configurar sessão e carrinho: ' . $e->getMessage();
            throw new LocalizedException(__('Erro ao configurar sessão e carrinho: %1', $e->getMessage()));
        }
    }

    /**
     * Handle success messages after quote creation
     *
     * @param array $errorMessages
     * @return void
     */
    protected function handleSuccessMessages(array $errorMessages): void
    {
        if (!empty($errorMessages)) {
            $this->messageManager->addWarningMessage(
                __('Cotação criada com alguns avisos: %1', implode('; ', $errorMessages))
            );
        } else {
            $this->messageManager->addSuccessMessage(__('Cotação criada e carregada com sucesso.'));
        }
    }

    /**
     * Handle execution errors
     *
     * @param \Exception $e
     * @param array $errorMessages
     * @return ResultInterface
     */
    protected function handleExecutionError(\Exception $e, array $errorMessages): ResultInterface
    {
        foreach ($errorMessages as $errorMessage) {
            $this->messageManager->addErrorMessage($errorMessage);
        }
        $this->messageManager->addErrorMessage($e->getMessage());
        return $this->redirectToCart();
    }

    /**
     * Redirect to cart page
     *
     * @return ResultInterface
     */
    protected function redirectToCart(): ResultInterface
    {
        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }
}
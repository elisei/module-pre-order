<?php

namespace O2TI\PreOrder\Model;

use Magento\Store\Model\StoreManagerInterface;
use O2TI\PreOrder\Model\QuoteSender\SendMail;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Customer\Model\Address\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Psr\Log\LoggerInterface;
use Throwable;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class QuoteSender - Handles email sending for quotes
 */
class QuoteSender
{
    /**
     * Configuration path for template
     */
    public const EMAIL_TEMPLATE_CONFIG_PATH = 'preorder/quote/template';

    /**
     * Configuration path for email identity
     */
    public const EMAIL_SENDER_CONFIG_PATH = 'preorder/quote/email_identity';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var Config
     */
    private $addressConfig;

    /**
     * @var Data
     */
    private $paymentHelper;

    /**
     * @var SendMail
     */
    private $sendMail;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var UrlInterface
     */
    private $frontendUrlBuilder;

    /**
     * QuoteSender constructor
     *
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param SenderResolverInterface $senderResolver
     * @param TimezoneInterface $timezone
     * @param Config $addressConfig
     * @param Data $paymentHelper
     * @param SendMail $sendMail
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param UrlInterface $frontendUrlBuilder
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        SenderResolverInterface $senderResolver,
        TimezoneInterface $timezone,
        Config $addressConfig,
        Data $paymentHelper,
        SendMail $sendMail,
        StoreManagerInterface $storeManager,
        Emulation $emulation,
        UrlInterface $frontendUrlBuilder
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->senderResolver = $senderResolver;
        $this->timezone = $timezone;
        $this->addressConfig = $addressConfig;
        $this->paymentHelper = $paymentHelper;
        $this->sendMail = $sendMail;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * Send quote email
     *
     * @param Quote $quote
     * @param string $hash
     * @param string|null $tracking
     *
     * @return void
     * @throws MailException
     * @throws LocalizedException
     * @throws Exception
     */
    public function send(Quote $quote, string $hash, ?string $tracking = null): void
    {
        try {
            if (empty($quote->getCustomerEmail())) {
                throw new LocalizedException(__('Customer email is not set'));
            }

            $storeId = $quote->getStoreId();
            $templateId = $this->getEmailTemplate($storeId);
            $from = $this->getSenderInfo($storeId);
            $email = $quote->getCustomerEmail();
            $templateVars = $this->getTemplateVars($quote, $hash, $tracking);

            $this->sendMail->send($templateId, $quote, $templateVars, $from, $email);
            $this->sendCopyEmail($templateId, $quote, $templateVars, $from, $storeId);
        } catch (Exception $e) {
            $this->logError($e, $quote);
            throw new MailException(__('Failed to send email: %1', $e->getMessage()));
        }
    }

    /**
     * Format date from datetime string
     *
     * @param string $date
     * @return string
     */
    private function formatDateTime(string $date): string
    {
        $timeZone = new DateTimeZone($this->timezone->getConfigTimezone());
        return DateTime::createFromFormat('Y-m-d H:i:s', $date)
            ->setTimezone($timeZone)
            ->format('M jS, Y g:i:sa T');
    }

    /**
     * Get customer note if notification is enabled
     *
     * @param Quote $quote
     * @return string
     */
    private function getCustomerNote(Quote $quote): string
    {
        if ($quote->getCustomerNoteNotify()) {
            return (string)$quote->getCustomerNote();
        }
        return '';
    }

    /**
     * Format address according to type
     *
     * @param Address $address
     * @param string $type
     * @return string|null
     */
    private function formatAddress(Address $address, string $type): ?string
    {
        $formatType = $this->addressConfig->getFormatByCode($type);
        if (!$address->getFirstname()) {
            $address->setFirstname(' ');
        }
        return $formatType->getRenderer()->renderArray($address->getData());
    }

    /**
     * Get payment info as HTML
     *
     * @param Quote $quote
     * @return string
     */
    private function getPaymentHtml(Quote $quote): string
    {
        try {
            return $this->paymentHelper->getInfoBlockHtml(
                $quote->getPayment(),
                $quote->getStoreId()
            );
        } catch (Throwable $t) {
            if ($t->getMessage() !== 'The payment method you requested is not available.') {
                $this->logger->error($t->getMessage(), ['exception' => $t]);
            }
        }
        return '';
    }

    /**
     * Get template variables
     *
     * @param Quote $quote
     * @param string $hash
     * @param string|null $tracking
     * @return array
     * @throws Exception
     */
    private function getTemplateVars(Quote $quote, string $hash, ?string $tracking = null): array
    {
        $this->emulation->startEnvironmentEmulation(
            $quote->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );

        try {
            $store = $this->storeManager->getStore($quote->getStoreId());
            $data = [
                '_secure' => true,
                '_nosid' => true,
                'hash' => $hash,
            ];
            if ($tracking) {
                $data['affiliate_code'] = $tracking;
            }
            
            $linkPay = $this->frontendUrlBuilder->setScope($quote->getStoreId())
                ->getUrl('preorder/index/quote', $data);

            return [
                'quote_id' => $quote->getId(),
                'quote_updated_at' => $this->formatDateTime($quote->getUpdatedAt()),
                'quote_comment' => $this->getCustomerNote($quote),
                'quote_show_shipping_address' => !$quote->getIsVirtual(),
                'quote_shipping_address' => $this->formatAddress($quote->getShippingAddress(), 'html'),
                'quote_billing_address' => $this->formatAddress($quote->getBillingAddress(), 'html'),
                'quote_is_not_virtual' => !$quote->getIsVirtual(),
                'quote_shipping_method' => $quote->getShippingAddress()->getShippingMethod(),
                'quote_shipping_description' => $quote->getShippingAddress()->getShippingDescription(),
                'quote_payment_html' => $this->getPaymentHtml($quote),
                'payment_url' => $linkPay
            ];
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }

    /**
     * Get email template ID
     *
     * @param int $storeId
     * @return string
     * @throws LocalizedException
     */
    private function getEmailTemplate(int $storeId): string
    {
        $templateId = $this->scopeConfig->getValue(
            self::EMAIL_TEMPLATE_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($templateId)) {
            throw new LocalizedException(__('Email template is not configured'));
        }

        return $templateId;
    }

    /**
     * Get sender information
     *
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    private function getSenderInfo(int $storeId): array
    {
        $sender = $this->scopeConfig->getValue(
            self::EMAIL_SENDER_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'general';

        try {
            $from = $this->senderResolver->resolve($sender, $storeId);
        } catch (Exception $e) {
            $this->logger->error('Failed to resolve sender: ' . $e->getMessage(), [
                'sender' => $sender,
                'store_id' => $storeId
            ]);
            $from = $this->senderResolver->resolve('general', $storeId);
        }

        if (empty($from) || empty($from['email']) || empty($from['name'])) {
            throw new LocalizedException(
                __('Invalid sender data. Please configure store email settings.')
            );
        }

        return $from;
    }

    /**
     * Send copy email if configured
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param int $storeId
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    private function sendCopyEmail(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        int $storeId
    ): void {
        $copyToEmail = $this->scopeConfig->getValue(
            'preorder/quote/copy_to',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!empty($copyToEmail)) {
            $this->sendMail->sendCopyTo($templateId, $quote, $templateVars, $from, $copyToEmail);
        }
    }

    /**
     * Log error information
     *
     * @param Exception $e
     * @param Quote $quote
     * @return void
     */
    private function logError(Exception $e, Quote $quote): void
    {
        $this->logger->error('Error sending quote email: ' . $e->getMessage(), [
            'quote_id' => $quote->getId(),
            'customer_email' => $quote->getCustomerEmail(),
            'store_id' => $quote->getStoreId(),
            'exception' => $e
        ]);
    }
}
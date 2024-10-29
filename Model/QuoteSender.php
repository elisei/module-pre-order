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

/**
 * Class QuoteNotifier
 *
 * @package O2TI\PreOrder\Model
 */
class QuoteSender
{
    const EMAIL_TEMPLATE_CONFIG_PATH = 'quote_email/quote/template';
    const EMAIL_SENDER_CONFIG_PATH = 'quote_email/quote/email_identity';

    /** @var LoggerInterface */
    private $logger;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var SenderResolverInterface */
    private $senderResolver;

    /** @var TimezoneInterface */
    private $timezone;

    /** @var Config */
    private $addressConfig;

    /** @var Data */
    private $paymentHelper;

    /** @var SendMail */
    private $sendMail;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Emulation */
    private $emulation;

    /** @var UrlInterface */
    private $frontendUrlBuilder;

    /**
     * QuoteSender constructor.
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
     * Function: send
     *
     * @param Quote $quote
     * @param String $hash
     *
     * @throws MailException
     * @throws LocalizedException
     * @throws Exception
     */
    public function send(Quote $quote, $hash)
    {
        $templateId = $this->scopeConfig->getValue(self::EMAIL_TEMPLATE_CONFIG_PATH);
        $email = $quote->getCustomerEmail();

        /** @var array $from */
        $from = $this->senderResolver->resolve(
            $this->scopeConfig->getValue(self::EMAIL_SENDER_CONFIG_PATH)
        );

        $templateVars = $this->getTemplateVars($quote, $hash);

        // send email to customer
        $this->sendMail->send($templateId, $quote, $templateVars, $from, $email);

        // send email to customer service
        if ($copyToEmail = $this->scopeConfig->getValue('sales_email/order/copy_to')) {
            $this->sendMail->sendCopyTo($templateId, $quote, $templateVars, $from, $copyToEmail);
        }
    }

    /**
     * Function: getFormattedDateFromDateTimeString
     *
     * @param string $date
     *
     * @return string
     */
    private function getFormattedDateFromDateTimeString(string $date)
    {
        $timeZone = new DateTimeZone($this->timezone->getConfigTimezone());
        return DateTime::createFromFormat('Y-m-d H:i:s', $date)->setTimezone($timeZone)->format('M jS, Y g:i:sa T');
    }

    /**
     * Function: getCustomerNote
     *
     * @param Quote $quote
     *
     * @return mixed|string|null
     */
    private function getCustomerNote(Quote $quote)
    {
        if ($quote->getCustomerNoteNotify()) {
            return $quote->getCustomerNote();
        }
        return '';
    }

    /**
     * Function: getFormattedAddress
     *
     * @param Address $address
     * @param string $type
     *
     * @return string|null
     */
    private function getFormattedAddress(Address $address, $type)
    {
        $formatType = $this->addressConfig->getFormatByCode($type);

        if (! $address->getFirstname()) {
            $address->setFirstname(' ');
        }
        /** @noinspection PhpUndefinedMethodInspection */
        return $formatType->getRenderer()->renderArray($address->getData());
    }

    /**
     * Get payment info block as html
     *
     * @param Quote $quote
     *
     * @return string
     * @throws Exception
     */
    private function getPaymentHtml(Quote $quote)
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
     * Function: getTemplateVars
     *
     * @param Quote $quote
     * @param String $hash
     *
     * @return array
     * @throws Exception
     */
    private function getTemplateVars(Quote $quote, $hash)
    {
        $this->emulation->startEnvironmentEmulation(
            $quote->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );

        try {
            $store = $this->storeManager->getStore($quote->getStoreId());

            $linkPay = $this->frontendUrlBuilder->setScope($quote->getStoreId())
                ->getUrl('preorder/index/quote', [
                    '_secure' => true,
                    '_nosid' => true,
                    'hash' => $hash
                ]);

            $templateVars = [
                'quote_id' => $quote->getId(),
                'quote_updated_at' => $this->getFormattedDateFromDateTimeString($quote->getUpdatedAt()),
                'quote_comment' => $this->getCustomerNote($quote),
                'quote_show_shipping_address' => !$quote->getIsVirtual(),
                'quote_shipping_address' => $this->getFormattedAddress($quote->getShippingAddress(), 'html'),
                'quote_billing_address' => $this->getFormattedAddress($quote->getBillingAddress(), 'html'),
                'quote_is_not_virtual' => !$quote->getIsVirtual(),
                'quote_shipping_method' => $quote->getShippingAddress()->getShippingMethod(),
                'quote_shipping_description' => $quote->getShippingAddress()->getShippingDescription(),
                'quote_payment_html' => $this->getPaymentHtml($quote),
                'payment_url' => $linkPay
            ];

            return $templateVars;
        } finally {
            // Make sure we always stop the emulation
            $this->emulation->stopEnvironmentEmulation();
        }
    }
}

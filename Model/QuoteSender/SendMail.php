<?php

declare(strict_types=1);

namespace O2TI\PreOrder\Model\QuoteSender;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;

/**
 * Handles email sending for pre-orders
 */
class SendMail
{
    /**
     * Configuration path for email enabled status
     */
    public const XML_PATH_EMAIL_ENABLED = 'preorder/quote/enabled';

    /**
     * Configuration path for email copy method
     */
    public const XML_PATH_EMAIL_COPY_METHOD = 'preorder/quote/copy_method';

    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Send email to customer
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param string $email
     * @param bool|string|array $cc
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    public function send(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $email,
        $cc = false
    ): void {
        if (!$this->isEmailEnabled($quote->getStoreId())) {
            return;
        }

        $this->sendEmail($templateId, $quote, $templateVars, $from, $email, $cc);
    }

    /**
     * Send copy to additional emails
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param string $copyTo
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    public function sendCopyTo(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $copyTo
    ): void {
        if (!$this->isEmailEnabled($quote->getStoreId())) {
            return;
        }

        $copyEmails = $this->prepareCopyEmails($copyTo);
        $copyMethod = $this->getCopyMethod($quote->getStoreId());

        if ($copyMethod === 'bcc') {
            $this->sendEmail(
                $templateId,
                $quote,
                $templateVars,
                $from,
                $copyEmails[0],
                array_slice($copyEmails, 1)
            );
        } else {
            $this->sendSeparateEmails($templateId, $quote, $templateVars, $from, $copyEmails);
        }
    }

    /**
     * Send email
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param string $email
     * @param bool|string|array $cc
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    private function sendEmail(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $email,
        $cc = false
    ): void {
        $transport = $this->prepareTransport($templateId, $quote, $templateVars, $from, $email, $cc);
        $transport->sendMessage();
    }

    /**
     * Check if email sending is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    private function isEmailEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EMAIL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Prepare transport for email sending
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param string $email
     * @param bool|string|array $cc
     * @return TransportInterface
     */
    private function prepareTransport(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $email,
        $cc = false
    ): TransportInterface {
        $transportBuilder = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $quote->getStoreId()])
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($email);

        $this->addCcRecipients($transportBuilder, $cc);

        return $transportBuilder->getTransport();
    }

    /**
     * Add CC recipients to transport builder
     *
     * @param TransportBuilder $transportBuilder
     * @param bool|string|array $cc
     * @return void
     */
    private function addCcRecipients(TransportBuilder $transportBuilder, $cc): void
    {
        if ($cc) {
            if (is_array($cc)) {
                foreach ($cc as $ccEmail) {
                    $transportBuilder->addBcc($ccEmail);
                }
            } else {
                $transportBuilder->addCc($cc);
            }
        }
    }

    /**
     * Get copy method from configuration
     *
     * @param int $storeId
     * @return string
     */
    private function getCopyMethod(int $storeId): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_COPY_METHOD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Prepare copy emails array
     *
     * @param string $copyTo
     * @return array
     */
    private function prepareCopyEmails(string $copyTo): array
    {
        return array_map('trim', explode(',', $copyTo));
    }

    /**
     * Send separate emails to each recipient
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param array $emails
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    private function sendSeparateEmails(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        array $emails
    ): void {
        foreach ($emails as $email) {
            if (!empty($email)) {
                $this->sendEmail($templateId, $quote, $templateVars, $from, $email);
            }
        }
    }
}
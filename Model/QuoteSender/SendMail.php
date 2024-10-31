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
    private $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

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
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    public function send(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $email
    ): void {
        if (!$this->isEmailEnabled($quote->getStoreId())) {
            return;
        }

        $this->sendBasicEmail($templateId, $quote, $templateVars, $from, $email);
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
        
        if ($this->getCopyMethod($quote->getStoreId()) === 'bcc') {
            $this->sendWithBcc($templateId, $quote, $templateVars, $from, $copyEmails);
            return;
        }
        
        $this->sendSeparateEmails($templateId, $quote, $templateVars, $from, $copyEmails);
    }

    /**
     * Send basic email without copies
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param string $email
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    private function sendBasicEmail(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $email
    ): void {
        $transport = $this->prepareBasicTransport($templateId, $quote, $templateVars, $from, $email);
        $transport->sendMessage();
    }

    /**
     * Send email with BCC recipients
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
    private function sendWithBcc(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        array $emails
    ): void {
        $mainRecipient = array_shift($emails);
        $transport = $this->prepareTransportWithBcc($templateId, $quote, $templateVars, $from, $mainRecipient, $emails);
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
     * Prepare basic transport for email sending
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param string $email
     * @return TransportInterface
     */
    private function prepareBasicTransport(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $email
    ): TransportInterface {
        return $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $quote->getStoreId()])
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($email)
            ->getTransport();
    }

    /**
     * Prepare transport with BCC recipients
     *
     * @param string $templateId
     * @param Quote $quote
     * @param array $templateVars
     * @param array $from
     * @param string $mainRecipient
     * @param array $bccRecipients
     * @return TransportInterface
     */
    private function prepareTransportWithBcc(
        string $templateId,
        Quote $quote,
        array $templateVars,
        array $from,
        string $mainRecipient,
        array $bccRecipients
    ): TransportInterface {
        $transportBuilder = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $quote->getStoreId()])
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($mainRecipient);

        foreach ($bccRecipients as $bccEmail) {
            $transportBuilder->addBcc($bccEmail);
        }

        return $transportBuilder->getTransport();
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
            if (empty($email)) {
                continue;
            }
            $this->sendBasicEmail($templateId, $quote, $templateVars, $from, $email);
        }
    }
}

<?php

declare(strict_types=1);

namespace O2TI\PreOrder\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\Session\Quote;

/**
 * Block responsible for quote creation and email sending
 */
class CreateQuote extends Template
{
    /**
     * @var ButtonList
     */
    private $buttonList;

    /**
     * @var Quote
     */
    private $quoteSession;

    /**
     * @param Quote $quoteSession
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Quote $quoteSession,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteSession = $quoteSession;
        $this->buttonList = $context->getButtonList();
        $this->addEmailQuoteButton();
    }

    /**
     * Add email quote button to the toolbar
     *
     * @return void
     */
    private function addEmailQuoteButton(): void
    {
        $this->buttonList->add(
            'email_quote',
            [
                'label' => __('Send Email Quote'),
                'id' => 'email_quote',
                'class' => 'primary'
            ],
            1,
            0,
            'toolbar'
        );
    }

    /**
     * Get URL for email quote action
     *
     * @return string
     */
    public function getEmailQuoteUrl(): string
    {
        return $this->getUrl('preorder/create/index');
    }
}
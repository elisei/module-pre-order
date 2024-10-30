<?php

declare(strict_types=1);

namespace O2TI\PreOrder\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\Session\Quote;

/**
 * Block responsible for providing quote ID
 */
class FixQuoteId extends Template
{
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
        $this->quoteSession = $quoteSession;
        parent::__construct($context, $data);
    }

    /**
     * Get current quote ID from session
     *
     * @return int
     */
    public function getQuoteId(): int
    {
        return (int)$this->quoteSession->getQuoteId();
    }
}

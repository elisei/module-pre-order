<?php

declare(strict_types=1);

namespace O2TI\PreOrder\Block\Adapted\Quote\Email\Items\Quote;

use Magento\Directory\Model\Currency;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Quote item price formatting block for email
 */
class DefaultQuoteItemPrice extends Template
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param Currency $currency
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Currency $currency,
        Context $context,
        array $data = []
    ) {
        $this->currency = $currency;
        parent::__construct($context, $data);
    }

    /**
     * Format price with currency
     *
     * @param float|string $price
     * @return string
     */
    public function formatPrice($price): string
    {
        return $this->currency->format($price);
    }
}
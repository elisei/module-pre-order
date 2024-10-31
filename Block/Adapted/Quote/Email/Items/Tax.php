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

namespace O2TI\PreOrder\Block\Adapted\Quote\Email\Items;

use Magento\Directory\Model\Currency;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Totals;
use Magento\Store\Model\Store;
use Magento\Tax\Block\Sales\Order\Tax as MagentoTax;
use Magento\Tax\Model\Config;

/**
 * Tax totals modification block
 */
class Tax extends MagentoTax
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param Currency $currency
     * @param Context $context
     * @param Config $taxConfig
     * @param array $data
     */
    public function __construct(
        Currency $currency,
        Context $context,
        Config $taxConfig,
        array $data = []
    ) {
        $this->currency = $currency;
        parent::__construct($context, $taxConfig, $data);
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

    /**
     * Initialize tax totals
     *
     * @return MagentoTax
     */
    public function initTotals(): MagentoTax
    {
        /** @var Totals $parent */
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        $store = $this->getStore();
        if ($this->shouldDisplayTax($store)) {
            $this->_addTax();
        }

        $this->initAdditionalTotals();

        return $this;
    }

    /**
     * Check if tax should be displayed
     *
     * @param Store $store
     * @return bool
     */
    private function shouldDisplayTax(Store $store): bool
    {
        $allowTax = $this->_source->getShippingAddress()->getTaxAmount() > 0
            || $this->_config->displaySalesZeroTax($store);
        $grandTotal = (float)$this->_source->getGrandTotal();

        return !$grandTotal || ($allowTax && !$this->_config->displaySalesTaxWithGrandTotal($store));
    }

    /**
     * Initialize additional totals (subtotal, shipping, discount, grand total)
     *
     * @return void
     */
    private function initAdditionalTotals(): void
    {
        $this->_initSubtotal();
        $this->_initShipping();
        $this->_initDiscount();
        $this->_initGrandTotal();
    }
}

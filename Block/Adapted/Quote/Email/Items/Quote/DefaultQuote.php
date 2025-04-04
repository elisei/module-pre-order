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

namespace O2TI\PreOrder\Block\Adapted\Quote\Email\Items\Quote;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Exception\RuntimeException;

/**
 * Default quote item renderer for email
 *
 * Adapted from \Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder
 */
class DefaultQuote extends Template
{
    /**
     * @var QuoteItem
     */
    protected $item;

    /**
     * Set quote item
     *
     * @param QuoteItem $item
     * @return $this
     */
    public function setItem(QuoteItem $item)
    {
        $this->item = $item;
        return $this;
    }

    /**
     * Get quote item
     *
     * @return QuoteItem
     * @throws RuntimeException
     */
    public function getItem(): QuoteItem
    {
        if ($this->item === null) {
            throw new RuntimeException('Quote item is not set');
        }
        return $this->item;
    }

    /**
     * Retrieve current quote model instance
     *
     * @return Quote
     */
    public function getQuote(): Quote
    {
        return $this->getItem()->getQuote();
    }

    /**
     * Get all available item options
     *
     * @return array
     */
    public function getItemOptions(): array
    {
        $result = [];
        $options = $this->getItem()->getProductOptions();
        
        if ($options) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }

        return $result;
    }

    /**
     * Get formatted option value
     *
     * @param string|array $value
     * @return string
     */
    public function getValueHtml($value): string
    {
        if (is_array($value)) {
            return sprintf(
                '%d x %s %s',
                $value['qty'],
                $this->escapeHtml($value['title']),
                $this->getItem()->getQuote()->formatPrice($value['price'])
            );
        }
        
        return $this->escapeHtml($value);
    }

    /**
     * Get item SKU
     *
     * @param QuoteItem $item
     * @return string
     */
    public function getSku(QuoteItem $item): string
    {
        return $item->getProductOptionByCode('simple_sku') ?: $item->getSku();
    }

    /**
     * Return product additional information block
     *
     * @return AbstractBlock
     */
    public function getProductAdditionalInformationBlock(): AbstractBlock
    {
        return $this->getLayout()->getBlock('additional.product.info');
    }

    /**
     * Get the html for item price
     *
     * @param QuoteItem $item
     * @return string
     */
    public function getItemPrice(QuoteItem $item): string
    {
        $block = $this->getLayout()->getBlock('item_price');
        if ($block) {
            $block->setItem($item);
            return $block->toHtml();
        }
        return '';
    }
}

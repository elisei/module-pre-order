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

namespace O2TI\PreOrder\Block\Adapted\Quote\Email;

use Magento\Directory\Model\Currency;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Block\Order\Totals as OrderTotals;

/**
 * Class to handle quote totals for email
 */
class Totals extends OrderTotals
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param Currency $currency
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Currency $currency,
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->currency = $currency;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Initialize order totals array
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return $this
     */
    protected function _initTotals()
    {
        $source = $this->getSource();

        $this->_totals = [];
        $this->_totals['subtotal'] = new DataObject(
            [
                'code' => 'subtotal',
                'value' => $source->getSubtotal(),
                'label' => __('Subtotal')
            ]
        );

        $this->addShippingTotal();
        $this->addDiscountTotal();

        $this->_totals['grand_total'] = new DataObject(
            [
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'value' => $source->getGrandTotal(),
                'label' => __('Grand Total')
            ]
        );

        return $this;
    }

    /**
     * Add shipping total to totals array
     *
     * @return void
     */
    private function addShippingTotal(): void
    {
        $source = $this->getSource();
        $shippingAddress = $source->getShippingAddress();

        if (!$source->getIsVirtual() &&
            $shippingAddress &&
            ($shippingAddress->getShippingAmount() > 0 ||
                (is_numeric($shippingAddress->getShippingAmount()) &&
                    $shippingAddress->getShippingDescription())
            )
        ) {
            $this->_totals['shipping'] = new DataObject(
                [
                    'code' => 'shipping',
                    'field' => 'shipping_amount',
                    'value' => $shippingAddress->getShippingAmount(),
                    'label' => __('Shipping & Handling')
                ]
            );
        }
    }

    /**
     * Add discount total to totals array
     *
     * @return void
     */
    private function addDiscountTotal(): void
    {
        $source = $this->getSource();
        $subtotalWithDiscount = $source->getSubtotalWithDiscount();
        $subtotal = $source->getSubtotal();

        if (is_numeric($subtotalWithDiscount) &&
            (float)$subtotalWithDiscount < (float)$subtotal
        ) {
            $this->_totals['discount'] = new DataObject(
                [
                    'code' => 'discount',
                    'field' => 'discount_amount',
                    'value' => (float)$subtotalWithDiscount - (float)$subtotal,
                    'label' => __('Discount')
                ]
            );
        }
    }

    /**
     * Format total value based on order currency
     *
     * @param DataObject $total
     * @return string
     */
    public function formatValue($total): string
    {
        if (!$total->getIsFormated()) {
            return $this->currency->format($total->getValue());
        }
        return $total->getValue();
    }
}

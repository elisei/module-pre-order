<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Block\Quote\Email;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Block\Items\AbstractItems;
use Psr\Log\LoggerInterface;

/**
 * Sales Order Email items.
 *
 * @api
 * @since 100.0.2
 */
class Items extends AbstractItems
{
    /**
     * @var CartRepositoryInterface|mixed|null
     */
    private $cartRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Items Constructor

     * @param Context $context
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface|null $cartRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ?CartRepositoryInterface $cartRepository = null,
        array $data = [],
    ) {
        $this->cartRepository = $cartRepository ?: ObjectManager::getInstance()->get(CartRepositoryInterface::class);
        parent::__construct($context, $data);
        $this->logger = $logger;
    }

    /**
     * Function: getQuote
     *
     * @return CartInterface|null
     */
    public function getQuote(): ?CartInterface
    {
        $quoteId = (int)$this->getData('quote_id');
        if (!$quoteId) {
            return null;
        }

        try {
            $quote = $this->cartRepository->get($quoteId);
            $this->setData('order', $quote);
            return $this->getData('order');
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Error retrieving quote id: $quoteId", ['exception' => $e]);
        }

        return null;
    }
}

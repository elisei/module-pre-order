<?php
/**
 * O2TI PreOrder Extension for Customer Comment Order
 *
 * @author    O2TI
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Observer for handling customer comments in PreOrder
 */
class SaveCustomerCommentToQuote implements ObserverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Save customer comment to quote
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $quoteSession = $observer->getData('session');
        if (!$quoteSession) {
            return;
        }

        $quote = $quoteSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return;
        }

        $request = $observer->getData('request_model');
        if (!$request) {
            return;
        }

        $orderData = $request->getParam('order', []);
        if (isset($orderData['customer_comment'])) {
            $quote->setCustomerComment($orderData['customer_comment']);
            $this->quoteRepository->save($quote);
        }
    }
}

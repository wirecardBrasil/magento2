<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper;

/**
 * Class SubjectReader - Reading data.
 */
class SubjectReader
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * SubjectReader constructor.
     *
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Reads payment from subject.
     *
     * @param array $subject
     *
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject): PaymentDataObjectInterface
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    /**
     * Reads store's ID, otherwise returns null.
     *
     * @param array $subject
     *
     * @return int|null
     */
    public function readStoreId(array $subject): ?int
    {
        $storeId = $subject['store_id'] ?? null;

        if (empty($storeId)) {
            try {
                $storeId = (int) $this->readPayment($subject)
                    ->getOrder()
                    ->getStoreId();
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
            } catch (\InvalidArgumentException $e) {
                // No store id is current set
            }
        }

        return $storeId ? (int) $storeId : null;
    }

    /**
     * Reads amount from subject.
     *
     * @param array $subject
     *
     * @return string
     */
    public function readAmount(array $subject): string
    {
        return (string) Helper\SubjectReader::readAmount($subject);
    }

    /**
     * Reads response from subject.
     *
     * @param array $subject
     *
     * @return array
     */
    public function readResponse(array $subject): array
    {
        return Helper\SubjectReader::readResponse($subject);
    }

    /**
     * Get Quote.
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get Order.
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }
}

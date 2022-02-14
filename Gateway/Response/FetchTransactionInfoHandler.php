<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class FetchTransactionInfoHandler - Handles reading responses to query transaction status.
 */
class FetchTransactionInfoHandler implements HandlerInterface
{
    /**
     * @const string
     */
    public const ACCEPT_PAID = 'PAID';

    /**
     * @const string
     */
    public const ACCEPT_PAID_ALTERNATIVE = 'AUTHORIZED';

    /**
     * @const string
     */
    public const DENNY_PAID = 'NOT_PAID';

    /**
     * @const string
     */
    public const DENNY_PAID_ALTERNATIVE = 'CANCELLED';

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        if ($response['STATUS'] === self::ACCEPT_PAID || $response['STATUS'] === self::ACCEPT_PAID_ALTERNATIVE) {
            $amount = $order->getBaseGrandTotal();
            $baseAmount = $order->getGrandTotal();
            $payment->registerAuthorizationNotification($amount);
            $payment->registerCaptureNotification($amount);
            $payment->setIsTransactionApproved(true);
            $payment->setIsTransactionDenied(false);
            $payment->setIsInProcess(true);
            $payment->setIsTransactionClosed(true);
            $payment->setAmountAuthorized($amount);
            $payment->setBaseAmountAuthorized($baseAmount);
            /** Pog dado ao erro 13466 */
            $comment = __('Your payment has been successfully received.');
            $history = $order->addStatusHistoryComment($comment);
            $history->setIsVisibleOnFront(1);
            $history->setIsCustomerNotified(1);
        }

        if ($response['STATUS'] === self::DENNY_PAID || $response['STATUS'] === self::DENNY_PAID_ALTERNATIVE) {
            $payment->setIsTransactionApproved(false);
            $payment->setIsTransactionDenied(true);
            $payment->setIsInProcess(true);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            /** Pog dado ao erro 13466 */
            $comment = $response['CANCELLATION_DETAILS_CUSTOMER'];
            $history = $order->addStatusHistoryComment($comment);
            $history->setIsVisibleOnFront(1);
            $history->setIsCustomerNotified(1);
            $comment = $response['CANCELLATION_DETAILS_ADMIN'];
            $history = $order->addStatusHistoryComment($comment);
            $history->setIsVisibleOnFront(0);
            $history->setIsCustomerNotified(0);
        }
    }
}

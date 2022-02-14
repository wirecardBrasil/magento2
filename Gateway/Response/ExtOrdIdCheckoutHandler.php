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
 * Class ExtOrdIdHandler - Handles reading responses for creating order.
 */
class ExtOrdIdCheckoutHandler implements HandlerInterface
{
    /**
     * @const string
     */
    public const EXTERNAL_ORDER_ID = 'EXT_ORD_ID';

    /**
     * @const string
     */
    public const TAG_INFO_CHECKOUT = 'checkout_url';

    /**
     * @const string
     */
    public const TAG_INFO_CC = 'checkout_cc';

    /**
     * @const string
     */
    public const TAG_INFO_BOLETO = 'checkout_boleto';

    /**
     * @const string
     */
    public const TAG_METHOD_NAME = 'method_name';

    /**
     * @const string
     */
    public const TAG_LINKS = '_links';

    /**
     * @const string
     */
    public const TAG_CHECKOUT = 'checkout';

    /**
     * @const string
     */
    public const TAG_PAY_CHECKOUT = 'payCheckout';

    /**
     * @const string
     */
    public const TAG_PAY_CC = 'payCreditCard';

    /**
     * @const string
     */
    public const TAG_PAY_BOLETO = 'payBoleto';

    /**
     * @const string
     */
    public const TAG_REDIRECT_HREF = 'redirectHref';

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
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

        $moipOrder = $response[self::EXTERNAL_ORDER_ID];

        $order->setExtOrderId($moipOrder);

        if ($payment->getMethod() === 'moip_magento2_checkout') {
            $payment->setAdditionalInformation(
                self::TAG_METHOD_NAME,
                'Moip Checkout'
            );
            // phpcs:ignore Generic.Files.LineLength
            $checkoutUrl = $response[self::TAG_LINKS][self::TAG_CHECKOUT][self::TAG_PAY_CHECKOUT][self::TAG_REDIRECT_HREF];
            $payment->setAdditionalInformation(
                self::TAG_INFO_CHECKOUT,
                $checkoutUrl
            );
            // phpcs:ignore Generic.Files.LineLength
            $checkoutCc = $response[self::TAG_LINKS][self::TAG_CHECKOUT][self::TAG_PAY_CC][self::TAG_REDIRECT_HREF];
            $payment->setAdditionalInformation(
                self::TAG_INFO_CC,
                $checkoutCc
            );
            // phpcs:ignore Generic.Files.LineLength
            $checkoutBoleto = $response[self::TAG_LINKS][self::TAG_CHECKOUT][self::TAG_PAY_BOLETO][self::TAG_REDIRECT_HREF];
            $payment->setAdditionalInformation(
                self::TAG_INFO_BOLETO,
                $checkoutBoleto
            );
        }
        $payment->setTransactionId($moipOrder);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
    }
}

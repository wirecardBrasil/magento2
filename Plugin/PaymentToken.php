<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Plugin;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class PaymentTokenManagementInterface - Fixed Issue #84.
 */
class PaymentToken
{
    /**
     * Around Save Token With Payment Link.
     *
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param callable                        $proceed
     * @param PaymentTokenInterface           $token
     * @param OrderPaymentInterface           $payment
     *
     * @return $proceed
     */
    public function aroundSaveTokenWithPaymentLink(
        PaymentTokenManagementInterface $paymentTokenManagement,
        callable $proceed,
        PaymentTokenInterface $token,
        OrderPaymentInterface $payment
    ): bool {
        $order = $payment->getOrder();

        if ($order->getCustomerIsGuest()) {
            return $proceed($token, $payment);
        }

        $existingToken = $paymentTokenManagement->getByGatewayToken(
            $token->getGatewayToken(),
            $payment->getMethodInstance()->getCode(),
            $order->getCustomerId()
        );

        if ($existingToken === null) {
            return $proceed($token, $payment);
        }

        $existingToken->addData($token->getData());

        return $proceed($existingToken, $payment);
    }
}

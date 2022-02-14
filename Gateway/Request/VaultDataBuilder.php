<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class VaultDataBuilder implements BuilderInterface
{
    /**
     * Additional options in request to gateway.
     */
    public const OPTIONS = 'options';

    /**
     * The option that determines whether the payment method associated with
     * the successful transaction should be stored in the Vault.
     */
    public const STORE_IN_VAULT_ON_SUCCESS = 'storeInVaultOnSuccess';

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
        $result = [];
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        if ($payment->getMethod() === 'moip_magento2_cc') {
            if (!empty($data[$payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)])) {
                $result[self::OPTIONS] = [
                    self::STORE_IN_VAULT_ON_SUCCESS => true,
                ];
            }
        }

        return $result;
    }
}

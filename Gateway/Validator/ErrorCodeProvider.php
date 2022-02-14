<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Gateway\Validator;

use Magento2\Result\Error;
use Magento2\Result\Successful;
use Magento2\Transaction;

/**
 * Class ErrorCodeProvider - Handles return from gateway.
 */
class ErrorCodeProvider
{
    /**
     * Error list.
     *
     * @param Successful|Error $response
     *
     * @return array
     */
    public function getErrorCodes($response): array
    {
        $result = [];
        if (!$response instanceof Error) {
            return $result;
        }

        $collection = $response->errors;

        foreach ($collection->deepAll() as $error) {
            $result[] = $error->code;
        }

        if (isset($response->transaction) && $response->transaction) {
            if ($response->transaction->status === Transaction::GATEWAY_REJECTED) {
                $result[] = $response->transaction->gatewayRejectionReason;
            }

            if ($response->transaction->status === Transaction::PROCESSOR_DECLINED) {
                $result[] = $response->transaction->processorResponseCode;
            }
        }

        return $result;
    }
}

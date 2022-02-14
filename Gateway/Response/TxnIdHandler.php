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
 * Class TxnIdHandler - Handles reading responses for creating payment.
 */
class TxnIdHandler implements HandlerInterface
{
    /**
     * @const string
     */
    public const TXN_ID = 'TXN_ID';

    /**
     * @const string
     */
    public const BOLETO_LINE_CODE = 'boleto_line_code';

    /**
     * @const string
     */
    public const BOLETO_PRINT_HREF = 'boleto_print_href';

    /**
     * @const string
     */
    public const PAYER_CC_NUMBER = 'cc_number';

    /**
     * @const string
     */
    public const PAYER_CC_TYPE = 'cc_type';

    /**
     * @const string
     */
    public const PAYER_CC_INSTALLMENTS = 'cc_installments';

    /**
     * @const string
     */
    public const PAYER_HOLDER_FULLNAME = 'cc_holder_fullname';

    /**
     * @const string
     */
    public const PAYER_HOLDER_BIRTH_DATE = 'cc_holder_birth_date';

    /**
     * @const string
     */
    public const PAYER_HOLDER_TAX_DOCUMENT = 'cc_holder_tax_document';

    /**
     * @const string
     */
    public const PAYER_HOLDER_PHONE = 'cc_holder_phone';

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

        if ($payment->getMethod() === 'moip_magento2_cc') {
            $paymentAddtional = $response['fundingInstrument'];

            if (isset($paymentAddtional['creditCard'])) {
                $ccType = $this->mapperCcType($paymentAddtional['creditCard']['brand']);
                $ccLast4 = $paymentAddtional['creditCard']['last4'];
                $ccHolderName = $paymentAddtional['creditCard']['holder']['fullname'];
            }
            $payment->setCcType($ccType);
            $payment->setCcLast4($ccLast4);
            $payment->setCcOwner($ccHolderName);
        }

        if ($payment->getMethod() === 'moip_magento2_cc_vault') {
            $paymentAddtional = $response['fundingInstrument'];

            if (isset($paymentAddtional['creditCard'])) {
                $ccType = $this->mapperCcType($paymentAddtional['creditCard']['brand']);
                $ccLast4 = $paymentAddtional['creditCard']['last4'];
                $ccHolderName = $paymentAddtional['creditCard']['holder']['fullname'];
            }
            $payment->setCcType($ccType);
            $payment->setCcLast4($ccLast4);
            $payment->setCcOwner($ccHolderName);

            $payment->setAdditionalInformation(
                self::PAYER_CC_TYPE,
                $ccType
            );
            $payment->setAdditionalInformation(
                self::PAYER_CC_NUMBER,
                'xxxx xxxx xxxx '.$ccLast4
            );
            $payment->setAdditionalInformation(
                self::PAYER_HOLDER_FULLNAME,
                $ccHolderName
            );
            $payment->setAdditionalInformation(
                self::PAYER_CC_INSTALLMENTS,
                $response['installmentCount']
            );
        }

        if ($payment->getMethod() === 'moip_magento2_boleto') {
            $payment->setAdditionalInformation(
                self::BOLETO_LINE_CODE,
                $response['fundingInstrument']['boleto']['lineCode']
            );
            $payment->setAdditionalInformation(
                self::BOLETO_PRINT_HREF,
                $response['_links']['payBoleto']['printHref']
            );
        }

        $payment->setTransactionId($response[self::TXN_ID]);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
    }

    /**
     * Get Type Cc by response payment.
     *
     * @param string $type
     */
    public function mapperCcType($type)
    {
        if ($type === 'MASTERCARD') {
            return 'MC';
        } elseif ($type === 'VISA') {
            return 'VI';
        } elseif ($type === 'AMEX') {
            return 'AE';
        } elseif ($type === 'DINERS') {
            return 'DN';
        } elseif ($type === 'HIPERCARD') {
            return 'HC';
        } elseif ($type === 'HIPER') {
            return 'HI';
        } elseif ($type === 'ELO') {
            return 'ELO';
        }
    }
}

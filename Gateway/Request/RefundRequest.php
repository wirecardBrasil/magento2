<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\Config\Config;
use Moip\Magento2\Model\Ui\ConfigProviderBoleto;

/**
 * Class RefundRequest - Refund data structure.
 */
class RefundRequest implements BuilderInterface
{
    /**
     * @var string
     */
    public const MOIP_ORDER_ID = 'moip_order_id';

    /**
     * @var string
     */
    public const BANK_NUMBER = 'moip_magento2_boleto_bank_number';

    /**
     * @var string
     */
    public const AGENCY_NUMBER = 'moip_magento2_boleto_agency_number';

    /**
     * @var string
     */
    public const AGENCY_CHECK_NUMBER = 'moip_magento2_boleto_agency_check_number';

    /**
     * @var string
     */
    public const ACCOUNT_NUMBER = 'moip_magento2_boleto_account_number';

    /**
     * @var string
     */
    public const ACCOUNT_CHECK_NUMBER = 'moip_magento2_boleto_account_check_number';

    /**
     * @var string
     */
    public const HOLDER_FULLNAME = 'moip_magento2_boleto_account_holder_fullname';

    /**
     * @var string
     */
    public const HOLDER_DOCUMENT_NUMBER = 'moip_magento2_boleto_account_holder_document_number';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Config
     */
    private $configPayment;

    /**
     * @param ConfigInterface $config
     * @param Config          $configPayment
     */
    public function __construct(
        ConfigInterface $config,
        Config $configPayment
    ) {
        $this->config = $config;
        $this->configPayment = $configPayment;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $result = [];

        $paymentDO = $buildSubject['payment'];

        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        $creditmemo = $payment->getCreditMemo();

        $total = $creditmemo->getGrandTotal();

        $result = [
            self::MOIP_ORDER_ID => $order->getExtOrderId(),
            'send'              => [
                'amount' => $this->configPayment->formatPrice($total),
            ],
        ];

        if ($order->getPayment()->getMethodInstance()->getCode() === ConfigProviderBoleto::CODE) {
            $bankNumber = $creditmemo->getData(self::BANK_NUMBER);
            $agencyNumber = $creditmemo->getData(self::AGENCY_NUMBER);
            $agencyCheckNumber = $creditmemo->getData(self::AGENCY_CHECK_NUMBER);
            $accountNumber = $creditmemo->getData(self::ACCOUNT_NUMBER);
            $accountCheckNumber = $creditmemo->getData(self::ACCOUNT_CHECK_NUMBER);
            $holderFullname = $creditmemo->getData(self::HOLDER_FULLNAME);
            $holderDocumment = $creditmemo->getData(self::HOLDER_DOCUMENT_NUMBER);

            $typeDocument = 'CPF';
            $taxDocument = preg_replace('/[^0-9]/', '', $holderDocumment);
            if (strlen($taxDocument) === 14) {
                $typeDocument = 'CNPJ';
            }

            $resultBoleto = [
                'send' => [
                    'amount'              => $this->configPayment->formatPrice($total),
                    'refundingInstrument' => [
                        'method'      => 'BANK_ACCOUNT',
                        'bankAccount' => [
                            'type'               => 'CHECKING',
                            'bankNumber'         => $bankNumber,
                            'agencyNumber'       => $agencyNumber,
                            'agencyCheckNumber'  => $agencyCheckNumber,
                            'accountNumber'      => $accountNumber,
                            'accountCheckNumber' => $accountCheckNumber,
                            'holder'             => [
                                'fullname'    => $holderFullname,
                                'taxDocument' => [
                                    'type'   => $typeDocument,
                                    'number' => $taxDocument,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $result = array_merge($result, $resultBoleto);
        }

        return $result;
    }
}

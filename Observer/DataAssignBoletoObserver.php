<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignBoletoObserver - Captures payment information by boleto.
 */
class DataAssignBoletoObserver extends AbstractDataAssignObserver
{
    /**
     * @const string
     */
    public const METHOD_NAME = 'method_name';

    /**
     * @const string
     */
    public const METHOD_NAME_TYPE = 'Boleto Bancário';

    /**
     * @const string
     */
    public const PAYER_FULLNAME = 'boleto_payer_fullname';

    /**
     * @const string
     */
    public const PAYER_TAX_DOCUMENT = 'boleto_payer_tax_document';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYER_FULLNAME,
        self::PAYER_TAX_DOCUMENT,
    ];

    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        $paymentInfo->setAdditionalInformation(
            self::METHOD_NAME,
            self::METHOD_NAME_TYPE
        );

        foreach ($this->addInformationList as $addInformationKey) {
            if (isset($additionalData[$addInformationKey])) {
                if ($additionalData[$addInformationKey]) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $additionalData[$addInformationKey]
                    );
                }
            }
        }
    }
}

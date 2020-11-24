<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class DataAssignObserverCc - Capture credit card payment information.
 */
class DataAssignObserverCc extends AbstractDataAssignObserver
{
    /**
     * @const Method Name Block
     */
    const METHOD_NAME = 'method_name';

    /**
     * @const Method Name
     */
    const METHOD_NAME_TYPE = 'Cartão de Crédito';

    /**
     * @const Hahs
     */
    const PAYER_HASH = 'cc_hash';

    /**
     * @const Credit Card Number
     */
    const PAYER_CC_NUMBER = 'cc_number';

    /**
     * @const Credit Card Type
     */
    const PAYER_CC_TYPE = 'cc_type';

    /**
     * @const Installment
     */
    const PAYER_CC_INSTALLMENTS = 'cc_installments';

    /**
     * @const Holder Full Nane
     */
    const PAYER_HOLDER_FULLNAME = 'cc_holder_fullname';

    /**
     * @const Holder Birth Date
     */
    const PAYER_HOLDER_BIRTH_DATE = 'cc_holder_birth_date';

    /**
     * @const Holder Tax Document
     */
    const PAYER_HOLDER_TAX_DOCUMENT = 'cc_holder_tax_document';

    /**
     * @const Holder Phone
     */
    const PAYER_HOLDER_PHONE = 'cc_holder_phone';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYER_HASH,
        self::PAYER_CC_TYPE,
        self::PAYER_CC_NUMBER,
        self::PAYER_CC_INSTALLMENTS,
        self::PAYER_HOLDER_FULLNAME,
        self::PAYER_HOLDER_TAX_DOCUMENT,
        self::PAYER_HOLDER_BIRTH_DATE,
        self::PAYER_HOLDER_PHONE,
    ];

    /**
     * @var
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param Observer $observer
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
                if ($addInformationKey === self::PAYER_CC_TYPE) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $this->getFullTypeName($additionalData[$addInformationKey])
                    );
                    continue;
                }
                if ($addInformationKey === self::PAYER_CC_NUMBER) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        'xxxx xxxx xxxx '.substr($additionalData[$addInformationKey], -4)
                    );
                    continue;
                }
                if ($additionalData[$addInformationKey]) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $additionalData[$addInformationKey]
                    );
                }
            }
        }
    }

    /**
     * Get Name for Cc Type.
     *
     * @parm string
     *
     * @return array
     */
    public function getFullTypeName(string $type): string
    {
        $mapper = $this->config->getCcTypesMapper();

        return $mapper[$type];
    }
}

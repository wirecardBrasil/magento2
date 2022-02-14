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
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class DataAssignObserverCc - Capture credit card payment information.
 */
class DataAssignObserverCc extends AbstractDataAssignObserver
{
    /**
     * @const string
     */
    public const METHOD_NAME = 'method_name';

    /**
     * @const string
     */
    public const METHOD_NAME_TYPE = 'Cartão de Crédito';

    /**
     * @const string
     */
    public const PAYER_HASH = 'cc_hash';

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
    public const PAYER_CC_EXP_M = 'cc_exp_month';

    /**
     * @const string
     */
    public const PAYER_CC_EXP_Y = 'cc_exp_year';

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
     * @const string
     */
    public const PAYER_CC_SAVE = 'is_active_payment_token_enabler';

    /**
     * @const string
     */
    public const PAYER_CC_CID = 'cc_cid';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYER_HASH,
        self::PAYER_CC_TYPE,
        self::PAYER_CC_EXP_M,
        self::PAYER_CC_EXP_Y,
        self::PAYER_CC_NUMBER,
        self::PAYER_CC_INSTALLMENTS,
        self::PAYER_HOLDER_FULLNAME,
        self::PAYER_HOLDER_TAX_DOCUMENT,
        self::PAYER_HOLDER_BIRTH_DATE,
        self::PAYER_HOLDER_PHONE,
        self::PAYER_CC_SAVE,
        self::PAYER_CC_CID,
    ];

    /**
     * @var Config
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
     * @param string $type
     *
     * @return string
     */
    public function getFullTypeName(string $type): string
    {
        $mapper = $this->config->getCcTypesMapper();

        return $mapper[$type];
    }
}

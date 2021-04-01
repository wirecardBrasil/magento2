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
class DataAssignObserverCcVault extends AbstractDataAssignObserver
{
    /**
     * @const Method Name Block
     */
    const METHOD_NAME = 'method_name';

    /**
     * @const Method Name
     */
    const METHOD_NAME_TYPE = 'Cartão de Crédito - Cofre';

    /**
     * @const Credit Card - CVV
     */
    const PAYER_CC_CVV = 'cc_cvv';

    /**
     * @const Installment
     */
    const PAYER_CC_INSTALLMENTS = 'cc_installments';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYER_CC_CVV,
        self::PAYER_CC_INSTALLMENTS,
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

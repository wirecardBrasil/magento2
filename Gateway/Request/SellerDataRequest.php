<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\Config\Config;
use Moip\Magento2\Gateway\Config\ConfigCc;
use Moip\Magento2\Gateway\Data\Order\OrderAdapterFactory;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class SellerDataRequest - Order seller amount structure.
 */
class SellerDataRequest implements BuilderInterface
{
    /**
     * Receivers block name.
     */
    public const RECEIVERS = 'receivers';

    /**
     * Moip Account block name.
     */
    public const RECEIVERS_MOIP_ACCOUNT = 'moipAccount';

    /**
     * Moip Account Id block name.
     */
    public const RECEIVERS_MOIP_ACCOUNT_ID = 'id';

    /**
     * Type Receiver block name.
     */
    public const RECEIVERS_TYPE = 'type';

    /**
     * Secondary Type Receiver.
     * required.
     */
    public const RECEIVERS_TYPE_SECONDARY = 'SECONDARY';

    /**
     * Amount Receiver block name.
     */
    public const RECEIVERS_AMOUNT = 'amount';

    /**
     * Fixed Receiver Type block name.
     */
    public const RECEIVERS_TYPE_FIXED = 'fixed';

    /**
     * Percent Receiver Type block name.
     */
    public const RECEIVERS_TYPE_PERCENT = 'percent';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    private $orderAdapterFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var configCc
     */
    private $configCc;

    /**
     * @var priceHelper
     */
    private $priceHelper;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     * @param ConfigCc            $configCc
     * @param PriceHelper         $checkoutHelper
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        ConfigCc $configCc,
        PriceHelper $checkoutHelper
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->configCc = $configCc;
        $this->priceHelper = $checkoutHelper;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $result = [];

        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();
        $useSplit = $this->config->getSplitValue('use_split', $storeId);
        if (!$useSplit) {
            return $result;
        }

        $addition = $orderAdapter->getTaxAmount();
        $interest = $orderAdapter->getBaseMoipInterestAmount();
        $grandTotal = $order->getGrandTotalAmount();
        $total = $grandTotal;

        $secondaryMPA = $this->config->getSplitValue('secondary_mpa', $storeId);
        $secondaryPercent = $this->config->getSplitValue('secondary_percent', $storeId);
        $commiUseShipping = $this->config->getSplitValue('secondary_percent_include_shipping', $storeId);
        $commiUseInterest = $this->config->getSplitValue('secondary_percent_include_interest', $storeId);

        if (!$commiUseInterest) {
            if ($interest > 0) {
                $total = $grandTotal - $interest;
            } elseif ($interest < 0) {
                $total = $grandTotal + $interest;
            }
        }

        if (!$commiUseShipping) {
            $total = $total - $orderAdapter->getShippingAmount();
        }

        $commission = $total * ($secondaryPercent / 100);

        $result[self::RECEIVERS][] = [
            self::RECEIVERS_MOIP_ACCOUNT => [
                self::RECEIVERS_MOIP_ACCOUNT_ID => $secondaryMPA,
            ],
            self::RECEIVERS_TYPE   => self::RECEIVERS_TYPE_SECONDARY,
            self::RECEIVERS_AMOUNT => [
                self::RECEIVERS_TYPE_FIXED => $this->config->formatPrice(round($commission, 2)),
            ],
        ];

        return $result;
    }
}

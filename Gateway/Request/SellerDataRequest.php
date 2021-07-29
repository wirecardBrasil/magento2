<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
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
    const RECEIVERS = 'receivers';

    /**
     * Moip Account block name.
     */
    const RECEIVERS_MOIP_ACCOUNT = 'moipAccount';

    /**
     * Moip Account Id block name.
     */
    const RECEIVERS_MOIP_ACCOUNT_ID = 'id';

    /**
     * Type Receiver block name.
     */
    const RECEIVERS_TYPE = 'type';

    /**
     * Secondary Type Receiver.
     * required.
     */
    const RECEIVERS_TYPE_SECONDARY = 'SECONDARY';

    /**
     * Amount Receiver block name.
     */
    const RECEIVERS_AMOUNT = 'amount';

    /**
     * Fixed Receiver Type block name.
     */
    const RECEIVERS_TYPE_FIXED = 'fixed';

    /**
     * Percent Receiver Type block name.
     */
    const RECEIVERS_TYPE_PERCENT = 'percent';

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
     * @param Config              $Config
     * @param ConfigCc            $ConfigCc
     * @param CheckoutHelper      $checkoutHelper
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
     * {@inheritdoc}
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
        $discount = $orderAdapter->getDiscountAmount();
        $total = $grandTotal + $interest;
        if ($interest > 0) {
            $total = $grandTotal - $interest;
        }

        $secondaryMPA = $this->config->getSplitValue('secondary_mpa', $storeId);
        $secondaryPercent = $this->config->getSplitValue('secondary_percent', $storeId);
        $commiUseShipping = $this->config->getSplitValue('secondary_percent_include_shipping', $storeId);
        $commiUseInterest = $this->config->getSplitValue('secondary_percent_include_interest', $storeId);

        if ($payment->getMethod() === 'moip_magento2_cc' || $payment->getMethod() === 'moip_magento2_cc_vault') {
            if ($installment = $payment->getAdditionalInformation('cc_installments')) {
                $interestInfo = $this->configCc->getInfoInterest($storeId);
                if ($installment > 1) {
                    $typeInstallment = $this->configCc->getTypeInstallment($storeId);
                    if ($interestInfo[$installment] > 0) {
                        $installmentInterest = $this->getInterestCompound($total, $interestInfo[$installment], $installment);
                        if ($typeInstallment === 'simple') {
                            $installmentInterest = $this->getInterestSimple($total, $interestInfo[$installment], $installment);
                        }

                        if ($installmentInterest) {
                            $installmentInterest = number_format((float) $installmentInterest, 2, '.', '');
                            $payment->setAdditionalInformation(
                                self::INSTALLMENT_INTEREST,
                                $this->priceHelper->currency($installmentInterest, true, false)
                            );
                            if (!$interest) {
                                $orderAdapter->setMoipInterestAmount($installmentInterest)->setBaseMoipInterestAmount($installmentInterest);
                            }
                            $addition = $addition + $installmentInterest;
                        }
                    }
                } elseif ((int) $installment === 1) {
                    if ($interestInfo[$installment] < 0) {
                        $totalWithDiscount = $grandTotal + ($interest * -1);
                        $discountInterest = $this->getInterestDiscount($totalWithDiscount, $interestInfo[$installment]);
                        $discountInterest = number_format((float) $discountInterest, 2, '.', '');

                        $payment->setAdditionalInformation(
                            self::INSTALLMENT_INTEREST,
                            $this->priceHelper->currency($discountInterest, true, false)
                        );
                        if (!$interest) {
                            $orderAdapter->setMoipInterestAmount($discountInterest)->setBaseMoipInterestAmount($discountInterest);
                        }
                        $interest = $discountInterest;
                    }
                }
            }
        }

        if (!$commiUseShipping) {
            $total = $total - $orderAdapter->getShippingAmount();
        }

        if ($commiUseInterest) {
            if ($interest > 0) {
                $total = $total + $addition;
            } elseif ($interest < 0) {
                $total = $total + $interest;
            }
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

    /**
     * Get Intereset Discount.
     *
     * @param $total
     * @param $interest
     *
     * @return string
     */
    public function getInterestDiscount($total, $interest)
    {
        if ($interest) {
            $taxa = $interest / 100;
            $valinterest = $total * $taxa;

            return $valinterest;
        }

        return 0;
    }

    /**
     * Get Intereset for Simple.
     *
     * @param $total
     * @param $interest
     * @param $portion
     *
     * @return string
     */
    public function getInterestSimple($total, $interest, $portion)
    {
        if ($interest) {
            $taxa = $interest / 100;
            $valinterest = $total * $taxa;

            return ($total + $valinterest) / $portion;
        }

        return 0;
    }

    /**
     * Get Intereset for Compound.
     *
     * @param $total
     * @param $interest
     * @param $portion
     *
     * @return string
     */
    public function getInterestCompound($total, $interest, $portion)
    {
        if ($interest) {
            $taxa = $interest / 100;

            return (($total * $taxa) * 1) / (1 - (pow(1 / (1 + $taxa), $portion)));
        }

        return 0;
    }
}

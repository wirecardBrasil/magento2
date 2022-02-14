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
 * Class DetailTotalsDataRequest - Payment amount structure.
 */
class DetailTotalsDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    public const TOTALS_AMOUNT = 'amount';

    /**
     * Grand Total Amount.
     * Require.
     */
    public const TOTALS_AMOUNT_GRAND_TOTAL = 'total';

    /**
     * The Currency. ISO 4217
     * Required.
     */
    public const TOTALS_AMOUNT_CURRENCY = 'currency';

    /**
     * Subtotals block name.
     */
    public const TOTALS_AMOUNT_SUBTOTALS = 'subtotals';

    /**
     * The Shipping.
     */
    public const TOTALS_AMOUNT_SUBTOTALS_SHIPPING = 'shipping';

    /**
     * The Discount.
     */
    public const TOTALS_AMOUNT_SUBTOTALS_DISCOUNT = 'discount';

    /**
     * The Addition.
     */
    public const TOTALS_AMOUNT_SUBTOTALS_ADDITION = 'addition';

    /**
     * The interest.
     */
    public const INSTALLMENT_INTEREST = 'cc_installment_interest';

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
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @var PriceHelper
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
        $addition = $orderAdapter->getTaxAmount();
        $interest = $orderAdapter->getBaseMoipInterestAmount();
        $grandTotal = $order->getGrandTotalAmount();
        $total = $grandTotal + $interest;
        if ($interest > 0) {
            $total = $grandTotal - $interest;
        }

        $discount = $orderAdapter->getDiscountAmount();

        if ($payment->getMethod() === 'moip_magento2_cc' || $payment->getMethod() === 'moip_magento2_cc_vault') {
            $interest = $this->getAdditionalTotals($payment, $grandTotal, $total, $interest, $storeId);
        }

        if ($interest > 0) {
            $addition = $addition + $interest;
        }

        if ($interest < 0) {
            $discount = $discount + $interest;
        }
        $discount = $discount * -1;
        $result[self::TOTALS_AMOUNT] = [
            self::TOTALS_AMOUNT_CURRENCY    => $order->getCurrencyCode(),
            self::TOTALS_AMOUNT_GRAND_TOTAL => ceil($this->config->formatPrice($grandTotal)),
            self::TOTALS_AMOUNT_SUBTOTALS   => [
                self::TOTALS_AMOUNT_SUBTOTALS_SHIPPING => ceil($this->config->formatPrice(
                    $orderAdapter->getShippingAmount()
                )),
                self::TOTALS_AMOUNT_SUBTOTALS_DISCOUNT => ceil($this->config->formatPrice($discount)),
                self::TOTALS_AMOUNT_SUBTOTALS_ADDITION => ceil($this->config->formatPrice($addition)),
            ],
        ];

        return $result;
    }

    /**
     * Get Addtional Totals Moip.
     *
     * @param OrderAdapterFactory $payment
     * @param float               $grandTotal
     * @param float               $total
     * @param float               $interest
     * @param int                 $storeId
     *
     * @return float
     */
    public function getAdditionalTotals($payment, $grandTotal, $total, $interest, $storeId)
    {
        if ($installment = $payment->getAdditionalInformation('cc_installments')) {
            // $this->moipInterest->saveMoipInterest($quoteId, (int)$installment);
            $interestInfo = $this->configCc->getInfoInterest($storeId);
            if ($installment > 1) {
                $typeInstallment = $this->configCc->getTypeInstallment($storeId);
                if ($interestInfo[$installment] > 0) {
                    // phpcs:ignore Generic.Files.LineLength
                    $installmentInterest = $this->getInterestCompound($total, $interestInfo[$installment], $installment);
                    if ($typeInstallment === 'simple') {
                        $installmentInterest = $this->getInterestSimple($total, $interestInfo[$installment]);
                    }

                    if ($installmentInterest) {
                        $installmentInterest = number_format((float) $installmentInterest, 2, '.', '');
                        $payment->setAdditionalInformation(
                            self::INSTALLMENT_INTEREST,
                            $this->priceHelper->currency($installmentInterest, true, false)
                        );
                        if (!$interest) {
                            // phpcs:ignore Generic.Files.LineLength
                            $orderAdapter->setMoipInterestAmount($installmentInterest)->setBaseMoipInterestAmount($installmentInterest);
                        }

                        return $installmentInterest;
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
                        // phpcs:ignore Generic.Files.LineLength
                        $orderAdapter->setMoipInterestAmount($discountInterest)->setBaseMoipInterestAmount($discountInterest);
                    }

                    return $discountInterest;
                }
            }
        }
    }

    /**
     * Get Intereset Discount.
     *
     * @param float $total
     * @param float $interest
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
     * @param float $total
     * @param float $interest
     *
     * @return float
     */
    public function getInterestSimple($total, $interest)
    {
        if ($interest) {
            $taxa = $interest / 100;

            return $total * $taxa;
        }

        return 0;
    }

    /**
     * Get Intereset for Compound.
     *
     * @param float $total
     * @param float $interest
     * @param int   $portion
     *
     * @return float
     */
    public function getInterestCompound($total, $interest, $portion)
    {
        if ($interest) {
            $taxa = $interest / 100;
            $calc = (($total * $taxa) * 1) / (1 - (pow(1 / (1 + $taxa), $portion)));

            return $total - $calc;
        }

        return 0;
    }
}

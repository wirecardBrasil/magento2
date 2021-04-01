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
 * Class DetailTotalsDataRequest - Payment amount structure.
 */
class DetailTotalsDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    const TOTALS_AMOUNT = 'amount';

    /**
     * Grand Total Amount.
     * Require.
     */
    const TOTALS_AMOUNT_GRAND_TOTAL = 'total';

    /**
     * The Currency. ISO 4217
     * Required.
     */
    const TOTALS_AMOUNT_CURRENCY = 'currency';

    /**
     * Subtotals block name.
     */
    const TOTALS_AMOUNT_SUBTOTALS = 'subtotals';

    /**
     * The Shipping.
     */
    const TOTALS_AMOUNT_SUBTOTALS_SHIPPING = 'shipping';

    /**
     * The Discount.
     */
    const TOTALS_AMOUNT_SUBTOTALS_DISCOUNT = 'discount';

    /**
     * The Addition.
     */
    const TOTALS_AMOUNT_SUBTOTALS_ADDITION = 'addition';

    /**
     * The interest.
     */
    const INSTALLMENT_INTEREST = 'cc_installment_interest';

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
        $addition = $orderAdapter->getTaxAmount();
        $total = $order->getGrandTotalAmount();

        if ($payment->getMethod() === 'moip_magento2_cc' || $payment->getMethod() === 'moip_magento2_cc_vault') {
            if ($installment = $payment->getAdditionalInformation('cc_installments')) {
                if ($installment > 1) {
                    $typeInstallment = $this->configCc->getTypeInstallment($storeId);
                    $interest = $this->configCc->getInfoInterest($storeId);
                    $installmentInterest = $this->getInterestCompound($total, $interest[$installment], $installment);
                    if ($typeInstallment === 'simple') {
                        $installmentInterest = $this->getInterestSimple($total, $interest[$installment], $installment);
                    }
                    if ($installmentInterest) {
                        $total_parcelado = $installmentInterest * $installment;
                        $additionalPrice = $total_parcelado - $total;
                        $additionalPrice = number_format((float) $additionalPrice, 2, '.', '');
                        $payment->setAdditionalInformation(
                            self::INSTALLMENT_INTEREST,
                            $this->priceHelper->currency($additionalPrice, true, false)
                        );
                        $addition = $addition + $additionalPrice;
                    }
                }
            }
        }
        $total = $total - $orderAdapter->getShippingAmount();
        $result[self::TOTALS_AMOUNT] = [
            self::TOTALS_AMOUNT_CURRENCY    => $order->getCurrencyCode(),
            self::TOTALS_AMOUNT_GRAND_TOTAL => $this->config->formatPrice($total),
            self::TOTALS_AMOUNT_SUBTOTALS   => [
                self::TOTALS_AMOUNT_SUBTOTALS_SHIPPING => $this->config->formatPrice(
                    $orderAdapter->getShippingAmount()
                ),
                self::TOTALS_AMOUNT_SUBTOTALS_DISCOUNT => -1 * $this->config->formatPrice(
                    $orderAdapter->getDiscountAmount()
                ),
                self::TOTALS_AMOUNT_SUBTOTALS_ADDITION => $this->config->formatPrice($addition),
            ],
        ];

        return $result;
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

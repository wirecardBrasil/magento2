<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Framework\Url;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\Config\Config;
use Moip\Magento2\Gateway\Config\ConfigCc;
use Moip\Magento2\Gateway\Config\ConfigCheckout;
use Moip\Magento2\Gateway\Data\Order\OrderAdapterFactory;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class CheckoutPreferencesRequest - Checkout Preferences Request structure.
 */
class CheckoutPreferencesRequest implements BuilderInterface
{
    /**
     * Checkout Preferences block name.
     */
    public const CHECKOUT_PREFERENCE = 'checkoutPreferences';

    /**
     * Redirect Urls block name.
     */
    public const REDIRECT_URLS = 'redirectUrls';

    /**
     * Url for Callback Success.
     */
    public const REDIRECT_URL_SUCCESS = 'urlSuccess';

    /**
     * Url for Callback Failure.
     */
    public const REDIRECT_URL_FAILURE = 'urlFailure';

    /**
     * Installments Block name.
     */
    public const INSTALLMENTS = 'installments';

    /**
     * Installments Qty Block name.
     */
    public const INSTALLMENTS_QTY = 'quantity';

    /**
     * Addition Qty Block name.
     */
    public const ADDITION = 'addition';

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
    private $configCheckout;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @var Url
     */
    private $urlHelper;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     * @param ConfigCc            $configCc
     * @param ConfigCheckout      $configCheckout
     * @param Url                 $urlHelper
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        ConfigCc $configCc,
        ConfigCheckout $configCheckout,
        Url $urlHelper
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->configCc = $configCc;
        $this->configCheckout = $configCheckout;
        $this->urlHelper = $urlHelper;
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

        $orderId = $orderAdapter->getId();
        $urlViewOrder = $this->urlHelper->getUrl('sales/order/history/', ['_scope' => $storeId, '_nosid' => true]);

        $result[self::CHECKOUT_PREFERENCE][self::REDIRECT_URLS] = [
            self::REDIRECT_URL_SUCCESS => $urlViewOrder,
            self::REDIRECT_URL_FAILURE => $urlViewOrder,
        ];
        $addition = 0;
        if ($payment->getAdditionalInformation('checkout_enable_installments')) {
            $installment = $payment->getAdditionalInformation('checkout_qty_installments');
            if ((int) $installment !== 1) {
                $interestInfo = $this->configCc->getInfoInterest($storeId);

                if ($interestInfo > 0) {
                    $grandTotal = $order->getGrandTotalAmount();
                    $typeInstallment = $this->configCc->getTypeInstallment($storeId);
                    $addition = $this->getInterestCompound($grandTotal, $interestInfo[$installment], $installment);
                    if ($typeInstallment === 'simple') {
                        $addition = $this->getInterestSimple($grandTotal, $interestInfo[$installment]);
                    }
                }
            }

            $result[self::CHECKOUT_PREFERENCE][self::INSTALLMENTS][] = [
                self::INSTALLMENTS_QTY => [
                    1,
                    $this->configCheckout->getMaxInstallments(),
                ],
                self::ADDITION => ceil($this->config->formatPrice($addition)),
            ];
        } else {
            $result[self::CHECKOUT_PREFERENCE][self::INSTALLMENTS][] = [
                self::INSTALLMENTS_QTY => [
                    1,
                ],
            ];
        }

        return $result;
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

<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
use Magento\Vault\Model\VaultPaymentInterface;
use Moip\Magento2\Gateway\Config\Config as ConfigBase;
use Moip\Magento2\Gateway\Config\ConfigCc;
use Moip\Magento2\Gateway\Config\ConfigCcVault;

/**
 * Class FormCc - Form for payment by cc.
 */
class FormCc extends Cc
{
    /**
     * Cc template.
     *
     * @var string
     */
    protected $_template = 'Moip_Magento2::form/cc.phtml';

    /**
     * @var Quote
     */
    protected $session;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var Data
     */
    private $paymentDataHelper;

    /**
     * @var ConfigBase
     */
    private $configBase;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @param Context     $context
     * @param Config      $paymentConfig
     * @param Quote       $session
     * @param ConfigCc    $configCc
     * @param configBase  $configBase
     * @param Data        $paymentDataHelper
     * @param PriceHelper $priceHelper
     * @param array       $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        Quote $session,
        ConfigCc $configCc,
        ConfigBase $configBase,
        Data $paymentDataHelper,
        PriceHelper $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->session = $session;
        $this->configBase = $configBase;
        $this->configCc = $configCc;
        $this->priceHelper = $priceHelper;
        $this->paymentDataHelper = $paymentDataHelper;
    }

    /**
     * Title - Cc.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->configCc->getTitle();
    }

    /**
     * Key Public - Cc.
     *
     * @var string
     */
    public function getKeyPublic()
    {
        return $this->configBase->getMerchantGatewayKeyPublic();
    }

    /**
     * Use birth date capture - Cc.
     *
     * @var bool
     */
    public function getBirthDateCapture()
    {
        return $this->configCc->getUseBirthDateCapture();
    }

    /**
     * Use tax document capture - Cc.
     *
     * @var bool
     */
    public function getTaxDocumentCapture()
    {
        return $this->configCc->getUseTaxDocumentCapture();
    }

    /**
     * Use phone capture - Cc.
     *
     * @var bool
     */
    public function getPhoneCapture()
    {
        return $this->configCc->getUsePhoneCapture();
    }

    /**
     * Get configured vault payment.
     *
     * @return VaultPaymentInterface
     */
    private function getVaultPayment()
    {
        return $this->paymentDataHelper->getMethodInstance(ConfigCcVault::METHOD);
    }

    /**
     * Check if vault enabled.
     *
     * @return bool
     */
    public function isVaultEnabled()
    {
        $vaultPayment = $this->getVaultPayment();

        return $vaultPayment->isActive();
    }

    /**
     * Use Enable Installments - Moip Checkout.
     *
     * @var bool
     */
    public function getEnableInstallments(): ?bool
    {
        return (bool) $this->configCheckout->getUseInstallments();
    }

    /**
     * Select Installment - Cc.
     *
     * @return array
     */
    public function getSelectInstallments(): array
    {
        $total = $this->session->getQuote()->getGrandTotal();
        $installments = $this->getInstallments($total);

        return $installments;
    }

    /**
     * Installments - Cc.
     *
     * @param float $amount
     *
     * @return array
     */
    public function getInstallments($amount): array
    {
        $typeInstallment = $this->configCc->getTypeInstallment();
        $interestByInstallment = $this->configCc->getInfoInterest();
        $plotlist = [];
        foreach ($interestByInstallment as $key => $_interest) {
            if ($key > 0) {
                $plotValue = $this->getInterestCompound($amount, $_interest, $key);
                if ($typeInstallment === 'simple') {
                    $plotValue = $this->getInterestSimple($amount, $_interest, $key);
                }
                $plotValue = number_format((float) $plotValue, 2, '.', '');
                $installmentPrice = $this->priceHelper->currency($plotValue, true, false);
                $plotlist[$key] = $key.__('x of ').$installmentPrice;
            }
        }

        return $plotlist;
    }

    /**
     * Interest Simple - Cc.
     *
     * @param float $total
     * @param float $interest
     * @param int   $portion
     *
     * @return float
     */
    public function getInterestSimple($total, $interest, $portion): float
    {
        if ($interest) {
            $taxa = $interest / 100;
            $valinterest = $total * $taxa;

            return ($total + $valinterest) / $portion;
        }

        return $total / $portion;
    }

    /**
     * Interest Compound - Cc.
     *
     * @param float $total
     * @param float $interest
     * @param int   $portion
     *
     * @return float
     */
    public function getInterestCompound($total, $interest, $portion): ?float
    {
        if ($interest) {
            $taxa = $interest / 100;

            return (($total * $taxa) * 1) / (1 - (pow(1 / (1 + $taxa), $portion)));
        }

        return 0;
    }
}

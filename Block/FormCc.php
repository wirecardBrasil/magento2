<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
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
use Moip\Magento2\Gateway\Config\Config as ConfigBase;
use Moip\Magento2\Gateway\Config\ConfigCc;

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
     * @var configCc
     */
    protected $configCc;

    /**
     * @var configProvider
     */
    protected $configProvider;

    /**
     * @var paymentDataHelper
     */
    private $paymentDataHelper;

    /**
     * @var configBase
     */
    private $configBase;

    /**
     * @var priceHelper
     */
    private $priceHelper;

    /**
     * @param Context    $context
     * @param Config     $paymentConfig
     * @param Quote      $session
     * @param ConfigCc   $configCc
     * @param configBase $configBase
     * @param Data       $paymentDataHelper
     * @param array      $data
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
     * @var string
     */
    public function getTitle()
    {
        return $this->configCc->getTitle();
    }

    /**
     * Select Installment - Cc.
     *
     * @var string
     */
    public function getSelectInstallments()
    {
        $total = $this->session->getQuote()->getGrandTotal();
        $installments = $this->getInstallments($total);

        return $installments;
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
     * Installments - Cc.
     *
     * @param float $amount
     *
     * @var string
     */
    public function getInstallments($amount)
    {
        $typeInstallment = $this->configCc->getTypeInstallment();
        $limitByInstallment = $this->configCc->getMaxInstallment();
        $limitInstallmentValue = $this->configCc->getMinInstallment();
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
     * @var float
     */
    public function getInterestSimple($total, $interest, $portion)
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
     * @var float
     */
    public function getInterestCompound($total, $interest, $portion)
    {
        if ($interest) {
            $taxa = $interest / 100;

            return (($total * $taxa) * 1) / (1 - (pow(1 / (1 + $taxa), $portion)));
        }

        return 0;
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
}

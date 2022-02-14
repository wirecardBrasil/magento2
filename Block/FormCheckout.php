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
use Moip\Magento2\Gateway\Config\ConfigCc;
use Moip\Magento2\Gateway\Config\ConfigCheckout;

/**
 * Class FormCheckout - Form for payment by moip checkout.
 */
class FormCheckout extends \Magento\Payment\Block\Form
{
    /**
     * Moip Checkout template.
     *
     * @var string
     */
    protected $_template = 'Moip_Magento2::form/checkout.phtml';

    /**
     * @var ConfigCheckout
     */
    protected $configCheckout;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var Quote
     */
    protected $session;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @param Context        $context
     * @param Quote          $session
     * @param ConfigCc       $configCc
     * @param ConfigCheckout $configCheckout
     * @param PriceHelper    $priceHelper
     */
    public function __construct(
        Context $context,
        Quote $session,
        ConfigCc $configCc,
        ConfigCheckout $configCheckout,
        PriceHelper $priceHelper
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->configCc = $configCc;
        $this->configCheckout = $configCheckout;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Title - Moip Checkout.
     *
     * @var string
     */
    public function getTitle(): string
    {
        return $this->configCheckout->getTitle();
    }

    /**
     * Instruction - Moip Checkout.
     *
     * @var string
     */
    public function getInstruction(): string
    {
        return $this->configCheckout->getInstructionCheckout();
    }

    /**
     * Use tax document capture - Moip Checkout.
     *
     * @var bool
     */
    public function getTaxDocumentCapture(): ?bool
    {
        return $this->configCheckout->getUseTaxDocumentCapture();
    }

    /**
     * Use Holder Name capture - Moip Checkout.
     *
     * @var bool
     */
    public function getNameCapture(): ?bool
    {
        return (bool) $this->configCheckout->getUseNameCapture();
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

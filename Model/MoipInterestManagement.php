<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model;

use Exception;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;
use Magento\Quote\Api\Data\TotalsInterface as QuoteTotalsInterface;
use Moip\Magento2\Api\Data\MoipInterestInterface;
use Moip\Magento2\Api\MoipInterestManagementInterface;
use Moip\Magento2\Gateway\Config\Config;
use Moip\Magento2\Gateway\Config\ConfigCc;

/**
 * Class MoipInterestManagement - Calc Insterest by Installment.
 */
class MoipInterestManagement implements MoipInterestManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var PriceCurrency
     */
    protected $priceCurrency;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * MoipInterestManagement constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param PriceCurrency           $priceCurrency,
     * @param Config                  $config,
     * @param ConfigCC                $configCc
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        PriceCurrency $priceCurrency,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->priceCurrency = $priceCurrency;
        $this->config = $config;
        $this->configCc = $configCc;
    }

    /**
     * Save moip interest number in the quote.
     *
     * @param int                   $cartId
     * @param MoipInterestInterface $moipInterest
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return null|string
     */
    public function saveMoipInterest(
        $cartId,
        MoipInterestInterface $moipInterest
    ) {
        $moip = [];
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $installment = $moipInterest->getInstallmentForInterest();
        $moipInterestValue = $this->calcInterest($quote, $installment);

        try {
            $quote->setData(MoipInterestInterface::MOIP_INTEREST_AMOUNT, $moipInterestValue);
            $quote->setData(MoipInterestInterface::BASE_MOIP_INTEREST_AMOUNT, $moipInterestValue);
            $this->quoteRepository->save($quote);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__('The moip interest # number could not be saved'));
        }

        $moip = [
            'interest_by_installment' => [
                'installment' => $installment,
                'interest'    => $moipInterestValue,
            ],
        ];

        return $moip;
    }

    /**
     * Calc value Interest.
     *
     * @param CartRepositoryInterface $quote
     * @param int                     $installment
     *
     * @return float
     */
    public function calcInterest($quote, $installment)
    {
        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);
        $grandTotal = $quote->getData(QuoteTotalsInterface::KEY_GRAND_TOTAL);
        $total = $grandTotal - $quote->getData(MoipInterestInterface::MOIP_INTEREST_AMOUNT);
        $installmentInterest = 0;

        if ($installment) {
            $typeInstallment = $this->configCc->getTypeInstallment($storeId);
            $interest = $this->configCc->getInfoInterest($storeId);
            if ($interest[$installment] > 0) {
                $installmentInterest = $this->getInterestCompound($total, $interest[$installment], $installment);
                if ($typeInstallment === 'simple') {
                    $installmentInterest = $this->getInterestSimple($total, $interest[$installment]);
                }
            } elseif ($interest[$installment] < 0) {
                $installmentInterest = $this->getInterestSimple($total, $interest[$installment]);
            }
        }

        return $this->priceCurrency->round($installmentInterest);
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

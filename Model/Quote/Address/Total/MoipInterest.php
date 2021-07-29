<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model\Quote\Address\Total;

use Magento\Checkout\Model\Session;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\QuoteValidator;

/**
 * Class MoipInterest - Model data Total Address.
 */
class MoipInterest extends AbstractTotal
{
    /**
     * @var string
     */
    protected $_code = 'moip_interest_amount';

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var QuoteValidator
     */
    protected $_quoteValidator = null;

    /**
     * @var PaymentInterface
     */
    protected $_payment;

    /**
     * Payment MoipInterest constructor.
     *
     * @param QuoteValidator   $quoteValidator
     * @param Session          $checkoutSession
     * @param PaymentInterface $payment
     */
    public function __construct(
        QuoteValidator $quoteValidator,
        Session $checkoutSession,
        PaymentInterface $payment
    ) {
        $this->_quoteValidator = $quoteValidator;
        $this->_checkoutSession = $checkoutSession;
        $this->_payment = $payment;
    }

    /**
     * Collect totals process.
     *
     * @param Quote                       $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total                       $total
     *
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $moipInterest = $quote->getMoipInterestAmount();
        $baseMoipInterest = $quote->getBaseMoipInterestAmount();

        $total->setMoipInterestAmount($moipInterest);
        $total->setBaseMoipInterestAmount($baseMoipInterest);

        $total->setTotalAmount('moip_interest_amount', $moipInterest);
        $total->setBaseTotalAmount('base_moip_interest_amount', $baseMoipInterest);

        $total->setGrandTotal((float) $total->getGrandTotal());
        $total->setBaseGrandTotal((float) $total->getBaseGrandTotal());

        return $this;
    }

    /**
     * Assign subtotal amount and label to address object.
     *
     * @param Quote $quote
     * @param Total $total
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(
        Quote $quote,
        Total $total
    ) {
        $result = null;
        $interest = $total->getMoipInterestAmount();
        $labelByInterest = $this->getLabelByInterest($interest);
        if ((int) $interest !== 0) {
            $result = [
                'code'  => $this->getCode(),
                'title' => $labelByInterest,
                'value' => $interest,
            ];
        }

        return $result;
    }

    /**
     * Get Subtotal label.
     *
     * @return Phrase
     */
    public function getLabel()
    {
        return __('Installment Interest');
    }

    /**
     * Get Subtotal label by Interest.
     *
     * @param $interest | float
     *
     * @return Phrase
     */
    public function getLabelByInterest($interest)
    {
        if ($interest >= 0) {
            return __('Installment Interest');
        }

        return __('Discount Cash');
    }
}

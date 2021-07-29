<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model;

use Magento\Quote\Model\QuoteIdMaskFactory;
use Moip\Magento2\Api\Data\MoipInterestInterface;
use Moip\Magento2\Api\GuestMoipInterestManagementInterface;
use Moip\Magento2\Api\MoipInterestManagementInterface;

/**
 * Class MoipInterestManagement - Calc Insterest by Installment.
 */
class GuestMoipInterestManagement implements GuestMoipInterestManagementInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var \Magento\Checkout\Api\ShippingInformationManagementInterface
     */
    protected $shippingInformationManagement;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory                      $quoteIdMaskFactory
     * @param \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement
     * @codeCoverageIgnore
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        MoipInterestManagementInterface $moipInterestManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->moipInterestManagement = $moipInterestManagement;
    }

    /**
     * {@inheritDoc}
     */
    public function saveMoipInterest(
        $cartId,
        MoipInterestInterface $moipInterest
    ) {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->moipInterestManagement->saveMoipInterest(
            $quoteIdMask->getQuoteId(),
            $moipInterest
        );
    }
}

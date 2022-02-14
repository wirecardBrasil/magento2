<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
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
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var MoipInterestManagementInterface
     */
    protected $moipInterestInterface;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory            $quoteIdMaskFactory
     * @param \Moip\Magento2\Api\MoipInterestManagementInterface $moipInterestInterface
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        MoipInterestManagementInterface $moipInterestInterface
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->moipInterestInterface = $moipInterestInterface;
    }

    /**
     * Save Moip Interest.
     *
     * @param int                   $cartId
     * @param MoipInterestInterface $moipInterest
     *
     * @return void
     */
    public function saveMoipInterest(
        $cartId,
        MoipInterestInterface $moipInterest
    ) {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->moipInterestInterface->saveMoipInterest(
            $quoteIdMask->getQuoteId(),
            $moipInterest
        );
    }
}

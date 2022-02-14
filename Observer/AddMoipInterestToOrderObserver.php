<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Moip\Magento2\Api\Data\MoipInterestInterface;

/**
 * Class AddMoipInterestToOrderObserver - Converte quote total in order.
 */
class AddMoipInterestToOrderObserver implements ObserverInterface
{
    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $moipInterest = $quote->getData(MoipInterestInterface::MOIP_INTEREST_AMOUNT);
        $baseMoipInterest = $quote->getData(MoipInterestInterface::BASE_MOIP_INTEREST_AMOUNT);
        $order->setData(MoipInterestInterface::MOIP_INTEREST_AMOUNT, $moipInterest);
        $order->setData(MoipInterestInterface::BASE_MOIP_INTEREST_AMOUNT, $baseMoipInterest);

        return $this;
    }
}

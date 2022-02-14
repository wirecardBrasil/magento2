<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model\Order\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class MoipInterest - Model data Total Creditmemo.
 */
class MoipInterest extends AbstractTotal
{
    /**
     * Collect Data.
     *
     * @param Creditmemo $creditmemo
     *
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $moipInterest = $order->getMoipInterestAmountInvoiced();
        $baseMoipInterest = $order->getBaseMoipInterestAmountInvoiced();

        if ((int) $moipInterest === 0) {
            return $this;
        }

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $moipInterest);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseMoipInterest);
        $creditmemo->setMoipInterestAmount($moipInterest);
        $creditmemo->setBaseMoipInterestAmount($baseMoipInterest);
        $order->setMoipInterestAmountRefunded($moipInterest);
        $order->setBaseMoipInterestAmountRefunded($baseMoipInterest);

        return $this;
    }
}

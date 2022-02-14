<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model\Order\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class MoipInterest - Model data Total Invoice.
 */
class MoipInterest extends AbstractTotal
{
    /**
     * Collect invoice subtotal.
     *
     * @param Invoice $invoice
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $moipInterest = $order->getMoipInterestAmount();
        $baseMoipInterestAmount = $order->getBaseMoipInterestAmount();

        if ((int) $moipInterest === 0) {
            return $this;
        }

        $invoice->setMoipInterestAmount($moipInterest);
        $invoice->setBaseMoipInterestAmount($baseMoipInterestAmount);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $moipInterest);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseMoipInterestAmount);
        $order->setMoipInterestAmountInvoiced($moipInterest);
        $order->setBaseMoipInterestAmountInvoiced($baseMoipInterestAmount);

        return $this;
    }
}

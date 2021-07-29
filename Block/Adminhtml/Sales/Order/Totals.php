<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\Sales\Order;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Class Totals - Invoice.
 */
class Totals extends Template
{
    /**
     * Retrieve current order model instance.
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get Source.
     *
     * @return source
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getOrder();
        $this->getSource();

        if (!$this->getSource()->getMoipInterestAmount() || (int) $this->getSource()->getMoipInterestAmount() === 0) {
            return $this;
        }

        $total = new DataObject(
            [
                'code'  => 'moip_interest',
                'value' => $this->getSource()->getMoipInterestAmount(),
                'label' => __('Moip Interest Amount'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}

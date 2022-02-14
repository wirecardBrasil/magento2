<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Class Totals - Creditmemo.
 */
class Totals extends Template
{
    /**
     * Get data (totals) source model.
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get Creditmemo.
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }

    /**
     * Initialize payment moip_interest totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getCreditmemo();
        $this->getSource();

        if (!$this->getSource()->getMoipInterestAmount()) {
            return $this;
        }

        $moip_interest = new DataObject(
            [
                'code'   => 'moip_interest',
                'strong' => false,
                'value'  => $this->getSource()->getMoipInterestAmount(),
                'label'  => __('Moip Interest Amount'),
            ]
        );

        $this->getParentBlock()->addTotalBefore($moip_interest, 'grand_total');

        return $this;
    }
}

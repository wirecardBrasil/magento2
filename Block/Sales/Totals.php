<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Block\Sales;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Class Totals - Template.
 */
class Totals extends Template
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var DataObject
     */
    protected $_source;

    /**
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model.
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * @return store
     */
    public function getStore()
    {
        return $this->_order->getStore();
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Initialize payment moip_interest totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        if (!$this->_source->getMoipInterestAmount() || (int) $this->_source->getMoipInterestAmount() === 0) {
            return $this;
        }

        $valueInterest = $this->_source->getMoipInterestAmount();
        $label = $this->getLabelByInterest($valueInterest);
        $moipInterest = new DataObject(
            [
                'code'   => 'moip_interest',
                'strong' => false,
                'value'  => $valueInterest,
                'label'  => $label,
            ]
        );

        if ((int) $valueInterest !== 0.0000) {
            $parent->addTotal($moipInterest, 'moip_interest');
        }

        return $this;
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

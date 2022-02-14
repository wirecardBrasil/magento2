<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class InfoCheckout - Moip Checkout payment information.
 */
class InfoCheckout extends ConfigurableInfo
{
    /**
     * @var string
     */
    protected $_template = 'Moip_Magento2::info/checkout/instructions.phtml';

    /**
     * Returns label.
     *
     * @param string $field
     *
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view.
     *
     * @param string $field
     * @param string $value
     *
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        if (is_array($value)) {
            return implode('; ', $value);
        }

        if ($field == 'checkout_url') {
            $value = '<a href="'.$value.'">'.__('To make payment click here').'</a>';
        }

        return parent::getValueView($field, $value);
    }
}

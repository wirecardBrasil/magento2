<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\System\Config;

/**
 * Class TypeCPF - Defines tax document.
 */
class TypeCPF implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array attributesArrays
     */
    public function toOptionArray()
    {
        return [
            null       => __('Please select'),
            'customer' => __('by customer form (customer account)'),
            'address'  => __('by address form (checkout)'),
        ];
    }
}

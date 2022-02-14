<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TypeInterest - Define Type Interest.
 */
class TypeInterest implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'simple'   => __('Juros Simples'),
            'compound' => __('Juros Composto'),
        ];
    }
}

<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Customer\Model\Customer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class CustomerMap - Maps customer attributes.
 */
class CustomerMap implements ArrayInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Customer               $customer
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Customer $customer
    ) {
        $this->objectManager = $objectManager;
        $this->customer = $customer;
    }

    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $customer_attributes = $this->customer->getAttributes();
        $attributesArrays = [];
        $attributesArrays[] = ['label' => __('Please select'), 'value' => null];

        foreach ($customer_attributes as $atribute => $val) {
            if ($val) {
                $attributesArrays[] = [
                    'label' => $atribute,
                    'value' => $atribute,
                ];
            }
        }

        return $attributesArrays;
    }
}

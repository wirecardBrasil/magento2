<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Customer\Model\Address;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class AddressMap - Maps address attributes.
 */
class AddressMap implements ArrayInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Address                $address
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Address $address
    ) {
        $this->objectManager = $objectManager;
        $this->address = $address;
    }

    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $customer_attributes = $this->address->getAttributes();

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

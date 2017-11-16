<?php
namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class TypeCNPJ implements \Magento\Framework\Option\ArrayInterface
{

   public function toOptionArray()
    {
        return [
            'use_cpf' => __('will use the same value as the CPF'),
            'use_customer' => __ ('by customer form (customer account)'),
            'use_address' => __('by address form (checkout)'),
        ];
    }
}
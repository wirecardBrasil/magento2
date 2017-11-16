<?php
namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class TypeNameCompany implements \Magento\Framework\Option\ArrayInterface
{

   public function toOptionArray()
    {
        return [
            'customer' => __('by customer form (customer account)'),
            'address' => __('by address form (checkout)'),
        ];
    }
}
<?php
namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class TypeCPF implements \Magento\Framework\Option\ArrayInterface
{

   public function toOptionArray()
    {
        return [
            'customer' => 'Obtido pelo Customer form',
            'address' => 'Obtido pelo Address form',
        ];
    }
}
?>
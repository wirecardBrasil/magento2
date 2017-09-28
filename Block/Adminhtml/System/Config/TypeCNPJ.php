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
            'use_cpf' => 'Usar o mesmo campo do cpf',
            'use_customer' => 'Obtido pelo Customer form',
            'use_address' => 'Obtido pelo Address form',
        ];
    }
}
?>
<?php
namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Environment implements \Magento\Framework\Option\ArrayInterface
{

   public function toOptionArray()
    {
        return [
            'production' => 'Produção',
            'sandbox' => 'Sandbox - Ambiente de Teste',
        ];
    }
}
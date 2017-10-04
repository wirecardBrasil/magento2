<?php
namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class TypeInterest implements \Magento\Framework\Option\ArrayInterface
{

   public function toOptionArray()
    {
        return [
            'simple' => 'Juros Simples',
            'compound' => 'Juros Composto',
        ];
    }
}
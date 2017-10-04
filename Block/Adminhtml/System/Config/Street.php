<?php
namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Street implements \Magento\Framework\Option\ArrayInterface
{

   public function toOptionArray()
    {
        return [
            '0' => 'Do array de street 1ª posição',
            '1' => 'Do array de street 2ª posição',
            '2' => 'Do array de street 3ª posição',
            '3' => 'Do array de street 4ª posição'
        ];
    }
}
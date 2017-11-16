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
            '0' => '1st line of the street',
            '1' => '2st line of the street',
            '2' => '3st line of the street',
            '3' => '4st line of the street'
        ];
    }
}
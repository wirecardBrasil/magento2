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
            'production' => __('Production'),
            'sandbox' => __('Sandbox - Environment for tests'),
        ];
    }
}
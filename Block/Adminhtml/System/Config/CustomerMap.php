<?php
namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model;

class CustomerMap implements \Magento\Framework\Option\ArrayInterface
{


    protected $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $interface
    ) {
       $this->objectManager = $interface;
    }


	public function toOptionArray( $isMultiselect = false)
    {
        $customer_attributes = $this->objectManager->get('Magento\Customer\Model\Customer')->getAttributes();

        $attributesArrays = [];

           foreach($customer_attributes as $cal=>$val){
               $attributesArrays[] = [
                   'label' => $cal,
                   'value' => $cal
               ];
           }

        return $attributesArrays;
    }
}
<?php 
namespace Moip\Magento2\Model\Source;
class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    public function getAllowedTypes()
    {
    	$allowed = ['VI', 'MC', 'AE', 'DI', 'JCB', 'OT'];
        return $allowed;
    }
}
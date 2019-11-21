<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moip\Magento2\Block\Info;

/*use Moip\Moip;
use Moip\Auth\BasicAuth;*/

class Cc extends \Magento\Payment\Block\Info
{
  
   
    protected $_template = 'Moip_Magento2::info/cc.phtml';
   
    public function getInstallments(){
        $_info = $this->getInfo();
        $installments = ($_info->getAdditionalInformation('installments') == 1) ? 'pagamento à vista' : $_info->getAdditionalInformation('installments').'x';
        return $installments;
    }

    
    public function getBrand(){
        $_info = $this->getInfo();
        $CcType = $_info->getCcType();

        return ucfirst($CcType);
    }
   
    public function getLastNumber(){
        $_info = $this->getInfo();
        $last = "xxxx".$_info->getCcLast4();

        return $last;
    }

    public function getOwner(){
        $_info = $this->getInfo();
        $CcOwner =  $_info->getAdditionalInformation('fullname');
        return $CcOwner;
    }


}

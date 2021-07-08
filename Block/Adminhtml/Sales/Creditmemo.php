<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\Sales;

use Moip\Magento2\Model\Ui\ConfigProviderBoleto;

class Creditmemo extends \Magento\Backend\Block\Template
{
    const BANK_NUMBER = 'moip_magento2_boleto_bank_number';

    const AGENCY_NUMBER = 'moip_magento2_boleto_agency_number';

    const AGENCY_CHECK_NUMBER = 'moip_magento2_boleto_agency_check_number';

    const ACCOUNT_NUMBER = 'moip_magento2_boleto_account_number';

    const ACCOUNT_CHECK_NUMBER = 'moip_magento2_boleto_account_check_number';

    const HOLDER_FULLNAME = 'moip_magento2_boleto_account_holder_fullname';

    const HOLDER_DOCUMENT_NUMBER = 'moip_magento2_boleto_account_holder_document_number';

    const CREDITMEMO_COMMENT_TEXT = 'comment_text';

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->coreRegistry->registry('current_creditmemo');
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getCreditmemo()->getOrder();
    }

    /**
     * Check whether can refund to payment by boleto.
     *
     * @return bool
     */
    public function canRefundBoleto()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->getCreditmemo()->getOrder();

        if ($order->getPayment()->getMethodInstance()->getCode() === ConfigProviderBoleto::CODE) {
            return true;
        }

        return false;
    }

    /**
     * Json configuration for tooltip.
     *
     * @parms $field
     *
     * @return string json
     */
    public function getTooltipConfig($field)
    {
        $tooltipConfig = [
            'tooltip' => [
                'trigger'  => '[data-tooltip-trigger=moip_magento2_tooltip_'.$field.']',
                'action'   => 'click',
                'delay'    => 0,
                'track'    => false,
                'position' => 'top',
            ],
        ];

        return str_replace('"', "'", \Zend_Json::encode($tooltipConfig));
    }

    public function getBankNumber()
    {
        return $this->getCreditmemo()->getData(self::BANK_NUMBER);
    }

    public function getAgencyNumber()
    {
        return $this->getCreditmemo()->getData(self::AGENCY_NUMBER);
    }

    public function getAgencyCheckNumber()
    {
        return $this->getCreditmemo()->getData(self::AGENCY_CHECK_NUMBER);
    }

    public function getAccountNumber()
    {
        return $this->getCreditmemo()->getData(self::ACCOUNT_NUMBER);
    }

    public function getAccountCheckNumber()
    {
        return $this->getCreditmemo()->getData(self::ACCOUNT_CHECK_NUMBER);
    }
}

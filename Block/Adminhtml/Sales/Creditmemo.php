<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\Sales;

use Moip\Magento2\Model\Ui\ConfigProviderBoleto;

class Creditmemo extends \Magento\Backend\Block\Template
{
    /**
     * @const string
     */
    public const BANK_NUMBER = 'moip_magento2_boleto_bank_number';

    /**
     * @const string
     */
    public const AGENCY_NUMBER = 'moip_magento2_boleto_agency_number';

    /**
     * @const string
     */
    public const AGENCY_CHECK_NUMBER = 'moip_magento2_boleto_agency_check_number';

    /**
     * @const string
     */
    public const ACCOUNT_NUMBER = 'moip_magento2_boleto_account_number';

    /**
     * @const string
     */
    public const ACCOUNT_CHECK_NUMBER = 'moip_magento2_boleto_account_check_number';

    /**
     * @const string
     */
    public const HOLDER_FULLNAME = 'moip_magento2_boleto_account_holder_fullname';

    /**
     * @const string
     */
    public const HOLDER_DOCUMENT_NUMBER = 'moip_magento2_boleto_account_holder_document_number';

    /**
     * @const string
     */
    public const CREDITMEMO_COMMENT_TEXT = 'comment_text';

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @param Context  $context
     * @param Registry $registry
     * @param array    $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get Credit Memo.
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->coreRegistry->registry('current_creditmemo');
    }

    /**
     * Get Order.
     *
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
     * @param string $field
     *
     * @return json
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

    /**
     * Get Bank Number.
     *
     * @return string
     */
    public function getBankNumber()
    {
        return $this->getCreditmemo()->getData(self::BANK_NUMBER);
    }

    /**
     * Get Agency Number.
     *
     * @return string
     */
    public function getAgencyNumber()
    {
        return $this->getCreditmemo()->getData(self::AGENCY_NUMBER);
    }

    /**
     * Get Agency Check Number.
     *
     * @return string
     */
    public function getAgencyCheckNumber()
    {
        return $this->getCreditmemo()->getData(self::AGENCY_CHECK_NUMBER);
    }

    /**
     * Get Account Number.
     *
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->getCreditmemo()->getData(self::ACCOUNT_NUMBER);
    }

    /**
     * Get Account Check Number.
     *
     * @return string
     */
    public function getAccountCheckNumber()
    {
        return $this->getCreditmemo()->getData(self::ACCOUNT_CHECK_NUMBER);
    }

    /**
     * Get Name Input for Bank Number.
     *
     * @return string
     */
    public function getNameInputBankNumber()
    {
        return self::BANK_NUMBER;
    }

    /**
     * Get Name Input for Agency Number.
     *
     * @return string
     */
    public function getNameInputAgencyNumber()
    {
        return self::AGENCY_NUMBER;
    }

    /**
     * Get Name Input for Agency Check.
     *
     * @return string
     */
    public function getNameInputAgencyCheck()
    {
        return self::AGENCY_CHECK_NUMBER;
    }

    /**
     * Get Name Input for Account Number.
     *
     * @return string
     */
    public function getNameInputAccountNumber()
    {
        return self::ACCOUNT_NUMBER;
    }

    /**
     * Get Name Input for Account Check.
     *
     * @return string
     */
    public function getNameInputAccountCheck()
    {
        return self::ACCOUNT_CHECK_NUMBER;
    }

    /**
     * Get Name Input for Holder Name.
     *
     * @return string
     */
    public function getNameInputHolderName()
    {
        return self::HOLDER_FULLNAME;
    }

    /**
     * Get Name Input for Holder Document.
     *
     * @return string
     */
    public function getNameInputHolderDocument()
    {
        return self::HOLDER_DOCUMENT_NUMBER;
    }
}

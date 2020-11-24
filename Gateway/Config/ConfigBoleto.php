<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigBoleto - Returns form of payment configuration properties.
 */
class ConfigBoleto extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * Method code - Boleto.
     *
     * @const string
     */
    const METHOD = 'moip_magento2_boleto';

    /**
     * Active - Boleto.
     *
     * @const boolean
     */
    const ACTIVE = 'active';

    /**
     * Title - Boleto.
     *
     * @const string
     */
    const TITLE = 'title';

    /**
     * Instruction in Checkout - Boleto.
     *
     * @const string
     */
    const INSTRUCTION_CHECKOUT = 'instruction_checkout';

    /**
     * Expiration - Boleto.
     *
     * @const int
     */
    const EXPIRATION = 'expiration';

    /**
     * Printing instruction - Line 1 - Boleto.
     *
     * @const string
     */
    const INSTRUCTION_LINE_FIRST = 'instruction_lines_first';

    /**
     * Printing instruction - Line 2 - Boleto.
     *
     * @const string
     */
    const INSTRUCTION_LINE_SECOND = 'instruction_lines_second';

    /**
     * Printing instruction - Line 3 - Boleto.
     *
     * @const string
     */
    const INSTRUCTION_LINE_THIRD = 'instruction_lines_third';

    /**
     * Use tax document capture - Boleto.
     *
     * @const boolean
     */
    const USE_GET_TAX_DOCUMENT = 'get_tax_document';

    /**
     * Use name capture - Boleto.
     *
     * @const boolean
     */
    const USE_GET_NAME = 'get_name';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Date
     */
    private $date;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
    }

    /**
     * Get Payment configuration status.
     *
     * @return bool
     */
    public function isActive($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::ACTIVE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get title of payment.
     *
     * @return string|null
     */
    public function getTitle($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TITLE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction - Checkoout.
     *
     * @return string|null
     */
    public function getInstructionCheckout($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_CHECKOUT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Expiration.
     *
     * @return date
     */
    public function getExpiration($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('Y-m-d', strtotime("+{$due} days"));
    }

    /**
     * Get Expiration Formart.
     *
     * @return date
     */
    public function getExpirationFormat($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('d/m/Y', strtotime("+{$due} days"));
    }

    /**
     * Get Instruction Line First - For Boleto.
     *
     * @return string|null
     */
    public function getInstructionLineFirst($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_LINE_FIRST),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction Line Second - For Boleto.
     *
     * @return string|null
     */
    public function getInstructionLineSecond($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_LINE_SECOND),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction Line Third - For Boleto.
     *
     * @return string|null
     */
    public function getInstructionLineThird($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_LINE_THIRD),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if you use document capture on the form.
     *
     * @return string|null
     */
    public function getUseTaxDocumentCapture($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_TAX_DOCUMENT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if you use name capture on the form.
     *
     * @return string|null
     */
    public function getUseNameCapture($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_NAME),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

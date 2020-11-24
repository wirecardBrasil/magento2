<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigCc - Returns form of payment configuration properties.
 */
class ConfigCc extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * Method Code - Cc.
     *
     * @const string
     */
    const METHOD = 'moip_magento2_cc';

    /**
     * Cc Tyoes - Cc.
     *
     * @const array
     */
    const CC_TYPES = 'payment/moip_magento2_cc/cctypes';

    /**
     * CVV Enabled - Cc.
     *
     * @const boolean
     */
    const CVV_ENABLED = 'cvv_enabled';

    /**
     * Active - Cc.
     *
     * @const boolean
     */
    const ACTIVE = 'active';

    /**
     * Title - Cc.
     *
     * @const string
     */
    const TITLE = 'title';

    /**
     * Mapper CC.
     *
     * @const string
     */
    const CC_MAPPER = 'cctypes_moip_magento2_cc_mapper';

    /**
     * Use tax document capture - Cc.
     *
     * @const boolean
     */
    const USE_GET_TAX_DOCUMENT = 'get_tax_document';

    /**
     * Use birth date capture - Cc.
     *
     * @const boolean
     */
    const USE_GET_BIRTH_DATE = 'get_birth_date';

    /**
     * Use phone capture - Cc.
     *
     * @const boolean
     */
    const USE_GET_PHONE = 'get_phone';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Should the cvv field be shown.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isCvvEnabled($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CVV_ENABLED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
     * Get if you use birth date capture on the form.
     *
     * @return string|null
     */
    public function getUseBirthDateCapture($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_BIRTH_DATE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if you use phone capture on the form.
     *
     * @return string|null
     */
    public function getUsePhoneCapture($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_PHONE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Should the cc types.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getCcAvailableTypes($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CC_TYPES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Cc Mapper.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCcTypesMapper($storeId = null): array
    {
        $pathPattern = 'payment/%s/%s';

        $ccTypesMapper = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CC_MAPPER),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $result = json_decode($ccTypesMapper, true);

        return is_array($result) ? $result : [];
    }

    /**
     * Get info interest.
     *
     * @return array
     */
    public function getInfoInterest($storeId = null)
    {
        $juros = [];
        $juros['0'] = 0;
        $juros['1'] = 0;
        $juros['2'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_2',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['3'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_3',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['4'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_4',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['5'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_5',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['6'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_6',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['7'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_7',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['8'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_8',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['9'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_9',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['10'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_10',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['11'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_11',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $juros['12'] = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_12',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $juros;
    }

    /**
     * Get type Interest.
     *
     * @return string
     */
    public function getTypeInstallment($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_type_interest',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get min installment.
     *
     * @return int
     */
    public function getMinInstallment($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_min_installment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get max installment.
     *
     * @return int
     */
    public function getMaxInstallment($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_max_installment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

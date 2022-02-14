<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Moip\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigCc - Returns form of payment configuration properties.
 */
class ConfigCc extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'moip_magento2_cc';

    /**
     * @const string
     */
    public const CC_TYPES = 'payment/moip_magento2_cc/cctypes';

    /**
     * @const string
     */
    public const CVV_ENABLED = 'cvv_enabled';

    /**
     * @const string
     */
    public const ACTIVE = 'active';

    /**
     * @const string
     */
    public const TITLE = 'title';

    /**
     * @const string
     */
    public const CC_MAPPER = 'cctypes_moip_magento2_cc_mapper';

    /**
     * @const string
     */
    public const USE_GET_TAX_DOCUMENT = 'get_tax_document';

    /**
     * @const string
     */
    public const USE_GET_BIRTH_DATE = 'get_birth_date';

    /**
     * @const string
     */
    public const USE_GET_PHONE = 'get_phone';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json                 $json
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json,
        $methodCode = self::METHOD
    ) {
        PaymentConfig::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * Should the cvv field be shown.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isCvvEnabled($storeId = null): bool
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
     * @param int|null $storeId
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
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getTitle($storeId = null): ?string
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
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getUseTaxDocumentCapture($storeId = null): bool
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
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getUseBirthDateCapture($storeId = null): bool
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
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getUsePhoneCapture($storeId = null): bool
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
    public function getCcAvailableTypes($storeId = null): ?string
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

        $result = $this->json->unserialize($ccTypesMapper);

        return is_array($result) ? $result : [];
    }

    /**
     * Get info interest.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getInfoInterest($storeId = null): array
    {
        $juros = [];
        $juros['0'] = 0;
        $juros['1'] = -$this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_installment_1',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTypeInstallment($storeId = null): ?string
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
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMinInstallment($storeId = null): ?string
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
     * @param int|null $storeId
     *
     * @return int|null
     */
    public function getMaxInstallment($storeId = null): ?int
    {
        return (int) $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/installment_max_installment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get is enable instant purchase.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getEnableInstantPurchase($storeId = null): ?bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/instant_purchase_enable',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

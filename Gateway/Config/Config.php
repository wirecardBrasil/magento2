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
 * Class Config - Returns form of payment configuration properties.
 */
class Config extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'moip_magento2';

    /**
     * @const int
     */
    public const ROUND_UP = 100;

    /**
     * @const string
     */
    public const ENDPOINT_PRODUCTION = 'https://api.moip.com.br/v2/';

    /**
     * @const string
     */
    public const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * @const string
     */
    public const ENDPOINT_SANDBOX = 'https://sandbox.moip.com.br/v2/';

    /**
     * @const string
     */
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * @const string
     */
    public const CLIENT = 'Magento2';

    /**
     * @const string
     */
    public const CLIENT_VERSION = '2.0.0';

    /**
     * @const string
     */
    public const PATTERN_FOR_ATTRIBUTES = 'moip_magento2';

    /**
     * @const string
     */
    public const PATTERN_FOR_CREDENTIALS = 'moip_credentials';

    /**
     * @const string
     */
    public const OAUTH_URI = 'http://moip.o2ti.com/magento/redirect/';

    /**
     * @const string
     */
    public const OAUTH_SCOPE = 'RECEIVE_FUNDS,REFUND,MANAGE_ACCOUNT_INFO,DEFINE_PREFERENCES,RETRIEVE_FINANCIAL_INFO';

    /**
     * @const string
     */
    public const OAUTH_TOKEN_SANDBOX = '8OKLQFT5XQZXU7CKXX43GPJOMIJPMSMF';

    /**
     * @const string
     */
    public const OAUTH_KEY_SANDBOX = 'NT0UKOXS4ALNSVOXJVNXVKRLEOQCITHI5HDKW3LI';

    /**
     * @const string
     */
    public const URL_KEY_SANDBOX = 'https://sandbox.moip.com.br/v2/keys/';

    /**
     * @const string
     */
    public const ENDPOINT_OAUTH_SANDBOX = 'https://connect-sandbox.moip.com.br/oauth/authorize';

    /**
     * @const string
     */
    public const ENDPOINT_OAUTH_TOKEN_SANDBOX = 'https://connect-sandbox.moip.com.br/oauth/token';

    /**
     * @const string
     */
    public const ENDPOINT_PREFERENCES_SANDBOX = 'https://sandbox.moip.com.br/v2/preferences/notifications/';

    /**
     * @const string
     */
    public const APP_ID_SANDBOX = 'APP-9MUFQ39Y4CQU';

    /**
     * @const string
     */
    public const CLIENT_SECRECT_SANDBOX = '26xa86dbc7mhdyqq2w69vscvhz47cri';

    /**
     * @const string
     */
    public const ENDPOINT_OAUTH_PRODUCTION = 'https://connect.moip.com.br/oauth/authorize';

    /**
     * @const string
     */
    public const ENDPOINT_OAUTH_TOKEN_PRODUCTION = 'https://connect.moip.com.br/oauth/token';

    /**
     * @const string
     */
    public const OAUTH_TOKEN_PPRODUCTION = 'EVCHBAUMKM0U4EE4YXIA8VMC0KBEPKN2';

    /**
     * @const string
     */
    public const OAUTH_KEY_PRODUCTION = '4NECP62EKI8HRSMN3FGYOZNVYZOMBDY0EQHK9MHO';

    /**
     * @const string
     */
    public const URL_KEY_PRODUCTION = 'https://api.moip.com.br/v2/keys/';

    /**
     * @const string
     */
    public const ENDPOINT_PREFERENCES_PRODUCTION = 'https://api.moip.com.br/v2/preferences/notifications/';

    /**
     * @const string
     */
    public const APP_ID_PRODUCTION = 'APP-AKYBMMVU1FL1';

    /**
     * @const string
     */
    public const CLIENT_SECRECT_PRODUCTION = 'db9pavx8542khvsyn3s0tpxyu2gom2m';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    protected $json;

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
     * Formant Price.
     *
     * @param int $amount
     *
     * @return float
     */
    public function formatPrice($amount): float
    {
        return $amount * self::ROUND_UP;
    }

    /**
     * Gets the API endpoint URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiUrl($storeId = null): ?string
    {
        $environment = $this->getEnvironmentMode($storeId);

        return $environment === 'sandbox'
            ? self::ENDPOINT_SANDBOX
            : self::ENDPOINT_PRODUCTION;
    }

    /**
     * Gets the Environment Mode.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getEnvironmentMode($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        $environment = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'environment'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $environment === 'sandbox'
            ? self::ENVIRONMENT_SANDBOX
            : self::ENVIRONMENT_PRODUCTION;
    }

    /**
     * Gets the Merchant Gateway OAuth.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayOauth($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        $oauth = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'oauth_production'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $oauth = $this->scopeConfig->getValue(
                sprintf($pathPattern, self::METHOD, 'oauth_sandbox'),
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $oauth;
    }

    /**
     * Gets the Merchant Gateway Key Public.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayKeyPublic($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        $environment = $this->getEnvironmentMode($storeId);

        $keyPublic = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'key_public_production'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($environment === 'sandbox') {
            $keyPublic = $this->scopeConfig->getValue(
                sprintf($pathPattern, self::METHOD, 'key_public_sandbox'),
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $keyPublic;
    }

    /**
     * Gets the Merchant Gateway Capture Token.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayCaptureToken($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        $keyPublic = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'capture_token_production'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $keyPublic = $this->scopeConfig->getValue(
                sprintf($pathPattern, self::METHOD, 'capture_token_sandbox'),
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $keyPublic;
    }

    /**
     * Gets the Merchant Gateway Cancel Token.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayCancelToken($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        $keyPublic = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'cancel_token_production'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $keyPublic = $this->scopeConfig->getValue(
                sprintf($pathPattern, self::METHOD, 'cancel_token_sandbox'),
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $keyPublic;
    }

    /**
     * Gets the Merchant Gateway Refund Token.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayRefundToken($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        $keyPublic = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'refund_token_production'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $keyPublic = $this->scopeConfig->getValue(
                sprintf($pathPattern, self::METHOD, 'refund_token_sandbox'),
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $keyPublic;
    }

    /**
     * Gets the Merchant Gateway Username.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayUsername($storeId = null): ?string
    {
        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            return  $this->getAddtionalValue('merchant_gateway_username_sandbox', $storeId);
        } else {
            return  $this->getAddtionalValue('merchant_gateway_username', $storeId);
        }
    }

    /**
     * Get Statement Descriptor.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getStatementDescriptor($storeId = null): ?string
    {
        return  $this->getAddtionalValue('statement_descriptor', $storeId);
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
        $ccTypesMapper = $this->scopeConfig->getValue(
            'payment/moip_magento2_cc/cctypes_moip_magento2_cc_mapper',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $result = $this->json->unserialize($ccTypesMapper);

        return is_array($result) ? $result : [];
    }

    /**
     * Gets the AddtionalValues.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getAddtionalValue($field, $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Gets the SlipValue.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getSplitValue($field, $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Gets the Moip Category.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getMoipCategory($storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            'payment/moip_magento2/category',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

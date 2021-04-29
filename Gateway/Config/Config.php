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
 * Class Config - Returns form of payment configuration properties.
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * Method code - Base.
     *
     * @const string
     */
    const METHOD = 'moip_magento2';

    /**
     * Round up -  Used to define float in integers.
     *
     * @const int
     */
    const ROUND_UP = 100;

    /**
     * endpoint of production.
     *
     * @const string
     */
    const ENDPOINT_PRODUCTION = 'https://api.moip.com.br/v2/';

    /**
     * environment mode production.
     *
     * @const string
     */
    const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * endpoint of sandbox.
     *
     * @const string
     */
    const ENDPOINT_SANDBOX = 'https://sandbox.moip.com.br/v2/';

    /**
     * environment mode sandbox.
     *
     * @const string
     */
    const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * Client name.
     *
     * @const string
     * */
    const CLIENT = 'Magento2';

    /**
     * Client Version - API version.
     *
     * @const string
     */
    const CLIENT_VERSION = '2.0.0';

    /**
     * Config Pattern for Atribute.
     *
     * @const string
     */
    const PATTERN_FOR_ATTRIBUTES = 'moip_magento2';

    /**
     * Config Pattern for Credentials.
     *
     * @const string
     */
    const PATTERN_FOR_CREDENTIALS = 'moip_credentials';

    /**
     * URI For Oauth.
     *
     * @const string
     */
    const OAUTH_URI = 'http://moip.o2ti.com/magento/redirect/';

    /**
     * Scope App.
     *
     * @const string
     */
    const OAUTH_SCOPE = 'RECEIVE_FUNDS,REFUND,MANAGE_ACCOUNT_INFO,DEFINE_PREFERENCES,RETRIEVE_FINANCIAL_INFO';

    /**
     * Token App - Sandbox.
     *
     * @const string
     */
    const OAUTH_TOKEN_SANDBOX = '8OKLQFT5XQZXU7CKXX43GPJOMIJPMSMF';

    /**
     * Key App - Sandbox.
     *
     * @const string
     */
    const OAUTH_KEY_SANDBOX = 'NT0UKOXS4ALNSVOXJVNXVKRLEOQCITHI5HDKW3LI';

    /**
     * URI For Keys - Sandbox.
     *
     * @const string
     */
    const URL_KEY_SANDBOX = 'https://sandbox.moip.com.br/v2/keys/';

    /**
     * Endpoint For Oauth - Sandbox.
     *
     * @const string
     */
    const ENDPOINT_OAUTH_SANDBOX = 'https://connect-sandbox.moip.com.br/oauth/authorize';

    /**
     * Endpoint For Get Token Oauth - Sandbox.
     *
     * @const string
     */
    const ENDPOINT_OAUTH_TOKEN_SANDBOX = 'https://connect-sandbox.moip.com.br/oauth/token';

    /**
     * Endpoint For Preferences - Sandbox.
     *
     * @const string
     */
    const ENDPOINT_PREFERENCES_SANDBOX = 'https://sandbox.moip.com.br/v2/preferences/notifications/';

    /**
     * URI App Id - Sandbox.
     *
     * @const string
     */
    const APP_ID_SANDBOX = 'APP-9MUFQ39Y4CQU';

    /**
     * Secrect For Oauth - Sandbox.
     *
     * @const string
     */
    const CLIENT_SECRECT_SANDBOX = '26xa86dbc7mhdyqq2w69vscvhz47cri';

    /**
     * Endpoint For Oauth - Sandbox.
     *
     * @const string
     */
    const ENDPOINT_OAUTH_PRODUCTION = 'https://connect.moip.com.br/oauth/authorize';

    /**
     * Endpoint For Get Token Oauth - Sandbox.
     *
     * @const string
     */
    const ENDPOINT_OAUTH_TOKEN_PRODUCTION = 'https://connect.moip.com.br/oauth/token';

    /**
     * Token App - Sandbox.
     *
     * @const string
     */
    const OAUTH_TOKEN_PPRODUCTION = 'EVCHBAUMKM0U4EE4YXIA8VMC0KBEPKN2';

    /**
     * Key App - Sandbox.
     *
     * @const string
     */
    const OAUTH_KEY_PRODUCTION = '4NECP62EKI8HRSMN3FGYOZNVYZOMBDY0EQHK9MHO';

    /**
     * URI For Keys - Production.
     *
     * @const string
     */
    const URL_KEY_PRODUCTION = 'https://api.moip.com.br/v2/keys/';

    /**
     * Endpoint For Preferences - Sandbox.
     *
     * @const string
     */
    const ENDPOINT_PREFERENCES_PRODUCTION = 'https://api.moip.com.br/v2/preferences/notifications/';

    /**
     * URI App Id - Production.
     *
     * @const string
     */
    const APP_ID_PRODUCTION = 'APP-AKYBMMVU1FL1';

    /**
     * Secrect For Oauth - Production.
     *
     * @const string
     */
    const CLIENT_SECRECT_PRODUCTION = 'db9pavx8542khvsyn3s0tpxyu2gom2m';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null
    ) {
        \Magento\Payment\Gateway\Config\Config::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Formant Price.
     *
     * @param int $amount
     *
     * @return int
     */
    public function formatPrice($amount)
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
    public function getApiUrl($storeId = null)
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
    public function getEnvironmentMode($storeId = null): string
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
    public function getMerchantGatewayOauth($storeId = null): string
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
    public function getMerchantGatewayKeyPublic($storeId = null): string
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
    public function getMerchantGatewayCaptureToken($storeId = null): string
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
    public function getMerchantGatewayCancelToken($storeId = null): string
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
    public function getMerchantGatewayRefundToken($storeId = null): string
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
    public function getMerchantGatewayUsername($storeId = null): string
    {
        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            return  $this->getAddtionalValue('merchant_gateway_username_sandbox', $storeId);
        } else {
            return  $this->getAddtionalValue('merchant_gateway_username', $storeId);
        }
    }

    public function getStatementDescriptor($storeId = null)
    {
        return  $this->getAddtionalValue('statement_descriptor', $storeId);
    }

    /**
     * Cc Mapper.
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
        $result = json_decode($ccTypesMapper, true);

        return is_array($result) ? $result : [];
    }

    /**
     * Gets the AddtionalValues.
     *
     * @param string   $typePattern
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAddtionalValue($field, $storeId = null): string
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
     * @param string   $typePattern
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string
     */
    public function getSplitValue($field, $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

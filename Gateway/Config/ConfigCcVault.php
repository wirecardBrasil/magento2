<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

class ConfigCcVault extends PaymentConfig
{
    /**
     * @const string
     */
    public const CVV_ENABLED = 'cvv_enabled';

    /**
     * @const string
     */
    public const METHOD = 'moip_magento2_cc_vault';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = self::METHOD
    ) {
        PaymentConfig::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get If Use Cvv.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function useCvv($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CVV_ENABLED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

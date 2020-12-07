<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class ConfigCcVault extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * CVV Enabled - Cc Vault.
     *
     * @const string
     */
    const CVV_ENABLED = 'cvv_enabled';

    /**
     * Method Code - Cc Vault.
     *
     * @const string
     */
    const METHOD = 'moip_magento2_cc_vault';

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param null                 $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null
    ) {
        \Magento\Payment\Gateway\Config\Config::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @throws InputException
     * @throws NoSuchEntityException
     *
     * @return bool
     */
    public function useCvv($storeId = null)
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CVV_ENABLED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

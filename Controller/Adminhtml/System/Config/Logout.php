<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config as ConfigBase;

class Logout extends \Magento\Backend\App\Action
{
    protected $cacheTypeList;

    protected $cacheFrontendPool;

    protected $resultJsonFactory;

    protected $configInterface;

    protected $resourceConfig;

    protected $storeManager;

    protected $configBase;

    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        JsonFactory $resultJsonFactory,
        ConfigInterface $configInterface,
        Config $resourceConfig,
        ConfigBase $configBase,
        StoreManagerInterface $storeManager
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configInterface = $configInterface;
        $this->resourceConfig = $resourceConfig;
        $this->configBase = $configBase;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::logout');
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $resultJson = $this->resultJsonFactory->create();

        $this->setClearOauth();
        $this->setClearMpa();
        $this->cacheTypeList->cleanType('config');

        return $resultJson->setData([
            'messages' => 'Successfully.',
            'error'    => false,
        ]);
    }

    private function setClearOauth()
    {
        $environment = $this->configBase->getEnvironmentMode();
        $this->resourceConfig->deleteConfig(
            'payment/moip_magento2/oauth_'.$environment,
            'default',
            0
        );
        $this->resourceConfig->deleteConfig(
            'payment/moip_magento2/publickey_'.$environment,
            'default',
            0
        );

        return $this;
    }

    private function setClearMpa()
    {
        $environment = $this->configBase->getEnvironmentMode();
        $this->resourceConfig->deleteConfig(
            'payment/moip_magento2/mpa_'.$environment,
            'default',
            0
        );
        $this->resourceConfig->deleteConfig(
            'payment/moip_magento2/mpa_'.$environment,
            'default',
            0
        );

        return $this;
    }
}

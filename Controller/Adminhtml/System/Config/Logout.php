<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
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

/**
 * Class Logout - Clear Moip Config.
 */
class Logout extends \Magento\Backend\App\Action
{
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ConfigInterface
     */
    protected $configInterface;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context               $context
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param JsonFactory           $resultJsonFactory
     * @param ConfigInterface       $configInterface
     * @param Config                $resourceConfig
     * @param ConfigBase            $configBase
     * @param StoreManagerInterface $storeManager
     */
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

    /**
     * ACL - Check is Allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::logout');
    }

    /**
     * Excecute.
     *
     * @return json
     */
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

    /**
     * Set Clear oAuth.
     *
     * @return void
     */
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

    /**
     * Set Clear Mpa.
     *
     * @return void
     */
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

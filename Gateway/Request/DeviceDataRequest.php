<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Framework\HTTP\Header as HeaderClient;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class DeviceDataRequest - User Device Data Structure.
 */
class DeviceDataRequest implements BuilderInterface
{
    /**
     * Device data customer.
     */
    const DEVICE_DATA = 'device';

    /**
     * RemoteIP data.
     */
    const REMOTE_IP = 'ip';

    /**
     * RemoteUserAgent data.
     */
    const REMOTE_USER_AGENT = 'userAgent';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var remoteAddress
     */
    private $remoteAddress;

    /**
     * @var headerClient
     */
    private $headerClient;

    /**
     * @param RemoteAddress $remoteAddress
     * @param HeaderClient  $headerClient
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        RemoteAddress $remoteAddress,
        HeaderClient $headerClient,
        SubjectReader $subjectReader
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->headerClient = $headerClient;
        $this->subjectReader = $subjectReader;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $result = [];
        $ipCustomer = $this->remoteAddress->getRemoteAddress();
        if (empty($ipCustomer)) {
            $ipCustomer = $order->getXForwardedFor();
        }
        $result[self::DEVICE_DATA] = [
            self::REMOTE_IP         => $ipCustomer,
            self::REMOTE_USER_AGENT => $this->headerClient->getHttpUserAgent(),
        ];

        $paymentInfo = $paymentDO->getPayment();

        $paymentInfo->setAdditionalInformation(
            self::DEVICE_DATA,
            $result[self::DEVICE_DATA]
        );

        return $result;
    }
}

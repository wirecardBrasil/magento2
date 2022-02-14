<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\Config\Config;
use Moip\Magento2\Gateway\Data\Order\OrderAdapterFactory;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class AddressDataRequest - Address structure.
 */
class AddressDataRequest implements BuilderInterface
{
    /**
     * BillingAddress block name.
     */
    public const BILLING_ADDRESS = 'billingAddresses';

    /**
     * BillingAddress block name.
     */
    public const SHIPPING_ADDRESS = 'shippingAddress';

    /**
     * The street address. Maximum 255 characters
     * Required.
     */
    public const STREET = 'street';

    /**
     * The street number. 1 or 10 alphanumeric digits
     * Required.
     */
    public const STREET_NUMBER = 'streetNumber';

    /**
     * The district address. Maximum 255 characters
     * Required.
     */
    public const STREET_DISTRICT = 'district';

    /**
     * The complement address. Maximum 255 characters
     * Required.
     */
    public const STREET_COMPLEMENT = 'complement';

    /**
     * The postal code.
     * Required.
     */
    public const POSTAL_CODE = 'zipCode';

    /**
     * The ISO 3166-1 alpha-3.
     * Required.
     */
    public const COUNTRY_CODE = 'country';

    /**
     * The locality/city. 255 character maximum.
     * Required.
     */
    public const LOCALITY = 'city';

    /**
     * The state or province. The region must be a 2-letter abbreviation.
     * Required.
     */
    public const STATE = 'state';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    private $orderAdapterFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
    }

    /**
     * Value For Field Address.
     *
     * @param OrderAdapterFactory $adress
     * @param string              $field
     *
     * @return string|null
     */
    public function getValueForAddress($adress, $field): ?string
    {
        $value = (int) $this->config->getAddtionalValue($field);
        $limitSend = 57;

        if ($field === self::STREET) {
            $limitSend = 57;
        } elseif ($field === self::STREET_NUMBER) {
            $limitSend = 6;
        } elseif ($field === self::STREET_DISTRICT) {
            $limitSend = 60;
        } elseif ($field === self::STREET_COMPLEMENT) {
            $limitSend = 30;
        }

        if ($value === 0) {
            return substr($adress->getStreetLine1(), 0, $limitSend);
        } elseif ($value === 1) {
            return substr($adress->getStreetLine2(), 0, $limitSend);
        } elseif ($value === 2) {
            return substr($adress->getStreetLine3(), 0, $limitSend);
        } elseif ($value === 3) {
            /** contigência para sempre haver o bairro */
            if ($adress->getStreetLine4()) {
                return substr($adress->getStreetLine4(), 0, $limitSend);
            }
            if ($adress->getStreetLine3()) {
                return substr($adress->getStreetLine3(), 0, $limitSend);
            }
            if ($adress->getStreetLine1()) {
                return substr($adress->getStreetLine1(), 0, $limitSend);
            }
        }

        return substr($adress->getStreetLine1(), 0, $limitSend);
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $result = [];

        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();
        if ($billingAddress) {
            $result[CustomerDataRequest::CUSTOMER][self::BILLING_ADDRESS] = [
                self::POSTAL_CODE       => $billingAddress->getPostcode(),
                self::STREET            => $this->getValueForAddress($billingAddress, self::STREET),
                self::STREET_NUMBER     => $this->getValueForAddress($billingAddress, self::STREET_NUMBER),
                self::STREET_DISTRICT   => $this->getValueForAddress($billingAddress, self::STREET_DISTRICT),
                self::STREET_COMPLEMENT => $this->getValueForAddress($billingAddress, self::STREET_COMPLEMENT),
                self::LOCALITY          => $billingAddress->getCity(),
                self::STATE             => $billingAddress->getRegionCode(),
                self::COUNTRY_CODE      => 'BRA',
            ];
        }

        $shippingAddress = $orderAdapter->getShippingAddress();
        if ($shippingAddress) {
            $result[CustomerDataRequest::CUSTOMER][self::SHIPPING_ADDRESS] = [
                self::POSTAL_CODE       => $shippingAddress->getPostcode(),
                self::STREET            => $this->getValueForAddress($shippingAddress, self::STREET),
                self::STREET_NUMBER     => $this->getValueForAddress($shippingAddress, self::STREET_NUMBER),
                self::STREET_DISTRICT   => $this->getValueForAddress($shippingAddress, self::STREET_DISTRICT),
                self::STREET_COMPLEMENT => $this->getValueForAddress($shippingAddress, self::STREET_COMPLEMENT),
                self::LOCALITY          => $shippingAddress->getCity(),
                self::STATE             => $shippingAddress->getRegionCode(),
                self::COUNTRY_CODE      => 'BRA',
            ];
        }

        return $result;
    }
}

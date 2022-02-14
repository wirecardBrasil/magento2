<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\Data\Order\OrderAdapterFactory;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class CustomerDataRequest - Customer structure.
 */
class CustomerDataRequest implements BuilderInterface
{
    /**
     * Customer block name.
     */
    public const CUSTOMER = 'customer';

    /**
     * Unique user id.
     * Required.
     */
    public const OWN_ID = 'ownId';

    /**
     * The first name value must be less than or equal to 255 characters.
     * Required.
     */
    public const FIRST_NAME = 'firstName';

    /**
     * The last name value must be less than or equal to 255 characters.
     * Required.
     */
    public const LAST_NAME = 'lastName';

    /**
     * The full name value must be less than or equal to 255 characters.
     * Required.
     */
    public const FULL_NAME = 'fullname';

    /**
     * The customer birth Date. Date Y-MM-dd.
     * Required.
     */
    public const BIRTH_DATE = 'birthDate';

    /**
     * The customer’s company. 255 character maximum.
     * Required.
     */
    public const COMPANY = 'company';

    /**
     * The customer’s email address.
     * Required.
     */
    public const EMAIL = 'email';

    /**
     * Phone block name.
     */
    public const PHONE = 'phone';

    /*
     * Phone Country Code. Must be 2 characters and can (DDI)
     * Required.
     */
    public const PHONE_CONNTRY_CODE = 'countryCode';

    /*
     * Phone Area code. Must be 2 characters and can (DDD)
     * Required.
     */
    public const PHONE_AREA_CODE = 'areaCode';

    /*
     * Phone Number. Must be 8 - 9 characters
     * Required.
     */
    public const PHONE_NUMBER = 'number';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    private $orderAdapterFactory;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    /**
     * Value For Field Address.
     *
     * @param string $param_telefone
     * @param bool   $return_ddd
     *
     * @return string
     */
    public function getNumberOrDDD($param_telefone, $return_ddd = false)
    {
        $cust_ddd = '11';
        $cust_telephone = preg_replace('/[^0-9]/', '', $param_telefone);
        if (strlen($cust_telephone) == 11) {
            $str = strlen($cust_telephone) - 9;
            $indice = 9;
        } else {
            $str = strlen($cust_telephone) - 8;
            $indice = 8;
        }

        if ($str > 0) {
            $cust_ddd = substr($cust_telephone, 0, 2);
            $cust_telephone = substr($cust_telephone, $str, $indice);
        }
        if ($return_ddd === false) {
            $number = $cust_telephone;
        } else {
            $number = $cust_ddd;
        }

        return $number;
    }

    /**
     * StructurePhone.
     *
     * @param string $phone
     * @param string $defaultCountryCode
     *
     * @return array
     */
    public function structurePhone($phone, $defaultCountryCode)
    {
        return [
            self::PHONE_CONNTRY_CODE => (int) $defaultCountryCode,
            self::PHONE_AREA_CODE    => (int) $this->getNumberOrDDD($phone, true),
            self::PHONE_NUMBER       => (int) $this->getNumberOrDDD($phone),
        ];
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $name = null;

        $payment = $paymentDO->getPayment();

        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();

        $defaultCountryCode = '';

        if ($billingAddress->getCountryId() == 'BR') {
            $defaultCountryCode = '55';
        }

        $dob = $orderAdapter->getCustomerDob();
        $dob = $dob ? date('Y-m-d', strtotime($dob)) : '1985-10-10';

        if ($dob === date('Y-m-d')) {
            $dob = '1985-11-11';
        }

        //* sobrescreve na order o name caso seja capturado no formulario de pagamento
        if ($payment->getAdditionalInformation('boleto_payer_fullname')) {
            $name = $payment->getAdditionalInformation('boleto_payer_fullname');
        }

        if ($payment->getAdditionalInformation('checkout_payer_fullname')) {
            $name = $payment->getAdditionalInformation('checkout_payer_fullname');
        }

        if (!$name) {
            $name = $billingAddress->getFirstname().' '.$billingAddress->getLastname();
        }

        return [
            self::CUSTOMER => [
                self::OWN_ID     => $billingAddress->getEmail(),
                self::FULL_NAME  => $name,
                self::COMPANY    => $billingAddress->getCompany(),
                self::PHONE      => $this->structurePhone($billingAddress->getTelephone(), $defaultCountryCode),
                self::EMAIL      => $billingAddress->getEmail(),
                self::BIRTH_DATE => $dob,
            ],
        ];
    }
}

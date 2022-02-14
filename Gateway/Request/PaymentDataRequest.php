<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\Config\Config;
use Moip\Magento2\Gateway\Config\ConfigBoleto;
use Moip\Magento2\Gateway\Config\ConfigCc;
use Moip\Magento2\Gateway\Data\Order\OrderAdapterFactory;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class PaymentDataRequest - Payment data structure.
 */
class PaymentDataRequest implements BuilderInterface
{
    /**
     * Payment Instrument - Block name.
     */
    public const PAYMENT_INSTRUMENT = 'paymentInstrument';

    /**
     * Installment count - Number of payment installments.
     */
    public const INSTALLMENT_COUNT = 'installmentCount';

    /**
     * Statement descriptor - Invoice description.
     */
    public const STATEMENT_DESCRIPTOR = 'statementDescriptor';

    /**
     * Funding instrument - Block name.
     */
    public const FUNDING_INSTRUMENT = 'fundingInstrument';

    /**
     * Method - Block Name.
     */
    public const METHOD = 'method';

    /**
     * Credit card - Block name.
     */
    public const TYPE_CREDIT_CARD = 'creditCard';

    /**
     * Credit card store - Sets whether to store the card.
     */
    public const CREDIT_CARD_STORE = 'store';

    /**
     * Credit card hash - Card encryption data.
     */
    public const CREDIT_CARD_HASH = 'hash';

    /**
     * Credit card id - Card Id.
     */
    public const CREDIT_CARD_ID = 'id';

    /**
     * Credit card CVV - Card CVV data.
     */
    public const CREDIT_CARD_CVV = 'cvc';

    /**
     * Credit card holder - Block name.
     */
    public const CREDIT_HOLDER = 'holder';

    /**
     * Credit card holder full name.
     */
    public const CREDIT_HOLDER_FULLNAME = 'fullname';

    /**
     * Credit card holder birth date.
     */
    public const CREDIT_HOLDER_BIRTH_DATA = 'birthdate';

    /**
     * Credit card holder tax document - Block name.
     */
    public const CREDIT_HOLDER_TAX_DOCUMENT = 'taxDocument';

    /**
     * Credit card holder tax document type.
     */
    public const CREDIT_HOLDER_TAX_DOCUMENT_TYPE = 'type';

    /**
     * Credit card holder tax document number.
     */
    public const CREDIT_HOLDER_TAX_DOCUMENT_NUMBER = 'number';

    /**
     * Credit card holder phone - Block name.
     */
    public const CREDIT_HOLDER_PHONE = 'phone';

    /**
     * Credit card holder phone country code.
     */
    public const CREDIT_HOLDER_PHONE_COUNTRY_CODE = 'countryCode';

    /**
     * Credit card holder phone area code.
     */
    public const CREDIT_HOLDER_PHONE_AREA_CODE = 'areaCode';

    /**
     * Credit card holder phone number.
     */
    public const CREDIT_HOLDER_PHONE_NUMBER = 'number';

    /**
     * Boleto - Block name.
     */
    public const TYPE_BOLETO = 'boleto';

    /**
     * Boleto expiration date - Due date.
     */
    public const BOLETO_EXPIRATION_DATE = 'expirationDate';

    /**
     * Boleto instruction lines - Block name.
     */
    public const BOLETO_INSTRUCTION_LINES = 'instructionLines';

    /**
     * Boleto instruction lines first - First line impression data.
     */
    public const BOLETO_INSTRUCTION_LINES_FIRST = 'first';

    /**
     * Boleto instruction lines second - Second line impression data.
     */
    public const BOLETO_INSTRUCTION_LINES_SECOND = 'second';

    /**
     * Boleto instruction lines third - Third line impression data.
     */
    public const BOLETO_INSTRUCTION_LINES_THIRD = 'third';

    /**
     * Boleto logo Uri - Url logo.
     */
    public const BOLETO_LOGO_URI = 'logoUri';

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
     * @var Config Boleto
     */
    private $configBoleto;

    /**
     * @var Config Cc
     */
    private $configCc;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     * @param ConfigCc            $configCc
     * @param ConfigBoleto        $configBoleto
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        ConfigCc $configCc,
        ConfigBoleto $configBoleto
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
        $this->configCc = $configCc;
        $this->configBoleto = $configBoleto;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $result = [];

        if ($payment->getMethod() === 'moip_magento2_cc') {
            $result = $this->getDataPaymetCC($payment, $orderAdapter, $storeId);
        }
        if ($payment->getMethod() === 'moip_magento2_cc_vault') {
            $result = $this->getDataPaymetCCVault($payment, $storeId);
        }

        if ($payment->getMethod() === 'moip_magento2_boleto') {
            $result = $this->getDataPaymetBoleto($storeId);
        }

        return $result;
    }

    /**
     * Data for Boleto.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getDataPaymetBoleto($storeId)
    {
        $instruction = [];
        $instruction[self::PAYMENT_INSTRUMENT] = [
            self::FUNDING_INSTRUMENT => [
                self::STATEMENT_DESCRIPTOR => substr($this->config->getStatementDescriptor($storeId), 0, 13),
                self::METHOD               => 'BOLETO',
                self::TYPE_BOLETO          => [
                    self::BOLETO_EXPIRATION_DATE   => $this->configBoleto->getExpiration($storeId),
                    self::BOLETO_INSTRUCTION_LINES => [
                        // phpcs:ignore Generic.Files.LineLength
                        self::BOLETO_INSTRUCTION_LINES_FIRST  => $this->configBoleto->getInstructionLineFirst($storeId),
                        // phpcs:ignore Generic.Files.LineLength
                        self::BOLETO_INSTRUCTION_LINES_SECOND => $this->configBoleto->getInstructionLineSecond($storeId),
                        // phpcs:ignore Generic.Files.LineLength
                        self::BOLETO_INSTRUCTION_LINES_THIRD  => $this->configBoleto->getInstructionLineThird($storeId),
                    ],
                    self::BOLETO_LOGO_URI => null,
                ],
            ],
        ];

        return $instruction;
    }

    /**
     * Data for CC Vault.
     *
     * @param OrderAdapterFactory $payment
     * @param int                 $storeId
     *
     * @return array
     */
    public function getDataPaymetCCVault($payment, $storeId)
    {
        $instruction = [];
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        $instruction[self::PAYMENT_INSTRUMENT] = [
            self::INSTALLMENT_COUNT    => $payment->getAdditionalInformation('cc_installments') ?: 1,
            self::STATEMENT_DESCRIPTOR => substr($this->config->getStatementDescriptor($storeId), 0, 13),
            self::FUNDING_INSTRUMENT   => [
                self::METHOD           => 'CREDIT_CARD',
                self::TYPE_CREDIT_CARD => [
                    self::CREDIT_CARD_ID   => $paymentToken->getGatewayToken(),
                    self::CREDIT_CARD_CVV  => $payment->getAdditionalInformation('cc_cid'),
                ],
            ],
        ];

        $payment->unsAdditionalInformation('cc_cid');

        return $instruction;
    }

    /**
     * Data for CC.
     *
     * @param PaymentDataObjectInterface $payment
     * @param OrderAdapterFactory        $orderAdapter
     * @param int                        $storeId
     *
     * @return array
     */
    public function getDataPaymetCC($payment, $orderAdapter, $storeId)
    {
        $instruction = [];
        $phone = $payment->getAdditionalInformation('cc_holder_phone');
        if (!$phone) {
            $phone = $orderAdapter->getBillingAddress()->getTelephone();
        }
        $dob = $payment->getAdditionalInformation('cc_holder_birth_date');
        if (!$dob) {
            $dob = $orderAdapter->getCustomerDob() ? $orderAdapter->getCustomerDob() : '1985-10-10';
        }
        $stored = $payment->getAdditionalInformation('is_active_payment_token_enabler');
        $instruction[self::PAYMENT_INSTRUMENT] = [
            self::INSTALLMENT_COUNT    => $payment->getAdditionalInformation('cc_installments') ?: 1,
            self::STATEMENT_DESCRIPTOR => substr($this->config->getStatementDescriptor($storeId), 0, 13),
            self::FUNDING_INSTRUMENT   => [
                self::METHOD           => 'CREDIT_CARD',
                self::TYPE_CREDIT_CARD => [
                    self::CREDIT_CARD_HASH   => $payment->getAdditionalInformation('cc_hash'),
                    self::CREDIT_CARD_STORE  => (bool) $stored,
                    self::CREDIT_HOLDER      => [
                        self::CREDIT_HOLDER_FULLNAME   => $payment->getAdditionalInformation('cc_holder_fullname'),
                        self::CREDIT_HOLDER_BIRTH_DATA => date('Y-m-d', strtotime($dob)),
                        self::CREDIT_HOLDER_PHONE      => $this->structurePhone($phone),
                    ],

                ],
            ],
        ];

        $taxDocument = $payment->getAdditionalInformation('cc_holder_tax_document');
        if (!$taxDocument) {
            $taxDocument = $this->getValueForTaxDocument($orderAdapter);
        }

        $taxDocument = preg_replace('/[^0-9]/', '', $taxDocument);
        $typeDocument = 'CPF';
        if (strlen($taxDocument) === 14) {
            $typeDocument = 'CNPJ';
        }

        if ($typeDocument) {
            // phpcs:ignore Generic.Files.LineLength
            $instruction[self::PAYMENT_INSTRUMENT][self::FUNDING_INSTRUMENT][self::TYPE_CREDIT_CARD][self::CREDIT_HOLDER][self::CREDIT_HOLDER_TAX_DOCUMENT] = [
                self::CREDIT_HOLDER_TAX_DOCUMENT_TYPE   => $typeDocument,
                self::CREDIT_HOLDER_TAX_DOCUMENT_NUMBER => $taxDocument,
            ];
        }

        return $instruction;
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
            $result = $cust_telephone;
        } else {
            $result = $cust_ddd;
        }

        return $result;
    }

    /**
     * ValueForTaxDocument.
     *
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getValueForTaxDocument($orderAdapter)
    {
        $obtainTaxDocFrom = $this->config->getAddtionalValue('type_cpf');

        if ($obtainTaxDocFrom === 'customer') {
            $taxDocument = $orderAdapter->getCustomerTaxvat();
            $attTaxDocCus = $this->config->getAddtionalValue('cpf_for_customer');

            if ($attTaxDocCus !== 'taxvat') {
                $taxDocument = $orderAdapter->getData($attTaxDocCus);
            }
        } else {
            $taxDocument = $orderAdapter->getBillingAddress()->getVatId();
            $attTaxDocAddress = $this->config->getAddtionalValue('cpf_for_address');

            if ($attTaxDocAddress !== 'vat_id') {
                $taxDocument = $orderAdapter->getBillingAddress()->getData($attTaxDocAddress);
            }
        }

        // * Contigência para caso não haja o atributo informado pelo Admin busque me 2 outros campos comuns.
        if (!$taxDocument) {
            $taxDocument = $orderAdapter->getBillingAddress()->getVatId();

            $obtainTaxDocFrom = $this->config->getAddtionalValue('type_cpf');
            if ($obtainTaxDocFrom === 'customer') {
                $taxDocument = $orderAdapter->getCustomerTaxvat();
            }
        }

        return $taxDocument;
    }

    /**
     * StructurePhone.
     *
     * @param string $phone
     *
     * @return array
     */
    public function structurePhone($phone)
    {
        return [
            self::CREDIT_HOLDER_PHONE_COUNTRY_CODE => (int) 55,
            self::CREDIT_HOLDER_PHONE_AREA_CODE    => (int) $this->getNumberOrDDD($phone, true),
            self::CREDIT_HOLDER_PHONE_NUMBER       => (int) $this->getNumberOrDDD($phone),
        ];
    }
}

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
 * Class TaxDocumentDataRequest - Fiscal document data structure.
 */
class TaxDocumentDataRequest implements BuilderInterface
{
    /**
     * BillingAddress block name.
     */
    public const TAX_DOCUMENT = 'taxDocument';

    /**
     * The street address. Maximum 255 characters
     * Required.
     */
    public const TAX_DOCUMENT_TYPE = 'type';

    /**
     * The street number. 1 or 10 alphanumeric digits
     * Required.
     */
    public const TAX_DOCUMENT_NUMBER = 'number';

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
     * Get Value For Tax Document.
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

            if (!$taxDocument) {
                $taxDocument = $orderAdapter->getCustomerTaxvat();
            }
        }

        return $taxDocument;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $taxDocument = null;
        $typeDocument = 'CPF';
        $result = [];
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );
        //* sobrescreve na order o documento caso seja capturado no formulario de pagamento
        if ($payment->getAdditionalInformation('cc_holder_tax_document')) {
            $taxDocument = $payment->getAdditionalInformation('cc_holder_tax_document');
        }

        if ($payment->getAdditionalInformation('boleto_payer_tax_document')) {
            $taxDocument = $payment->getAdditionalInformation('boleto_payer_tax_document');
        }

        if ($payment->getAdditionalInformation('checkout_payer_tax_document')) {
            $taxDocument = $payment->getAdditionalInformation('checkout_payer_tax_document');
        }

        if (!$taxDocument) {
            $taxDocument = $this->getValueForTaxDocument($orderAdapter);
        }

        $taxDocument = preg_replace('/[^0-9]/', '', $taxDocument);

        if (strlen($taxDocument) === 14) {
            $typeDocument = 'CNPJ';
        }

        if ($typeDocument) {
            $result[CustomerDataRequest::CUSTOMER][self::TAX_DOCUMENT] = [
                self::TAX_DOCUMENT_TYPE   => $typeDocument,
                self::TAX_DOCUMENT_NUMBER => $taxDocument,
            ];
        }

        return $result;
    }
}

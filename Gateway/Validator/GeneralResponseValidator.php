<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class GeneralResponseValidator - Handles return from gateway.
 */
class GeneralResponseValidator extends AbstractValidator
{
    /**
     * The result code.
     */
    public const RESULT_CODE_SUCCESS = '1';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader          $subjectReader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->resultFactory = $resultFactory;
        $this->subjectReader = $subjectReader;
    }

    /**
     * Validate.
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = $this->subjectReader->readResponse($validationSubject);
        $isValid = $response['RESULT_CODE'];
        $errorCodes = [];
        $errorMessages = [];

        if (!$isValid) {
            if (isset($response['messages']['message']['code'])) {
                $errorCodes[] = $response['messages']['message']['code'];
                $errorMessages[] = $response['messages']['message']['text'];
            } elseif (isset($response['messages']['message']['errors'])) {
                foreach ($response['messages']['message']['errors'] as $message) {
                    $errorCodes[] = $message['code'];
                    $errorMessages[] = $message['description'];
                }
            } elseif (isset($response['errors'])) {
                foreach ($response['errors'] as $message) {
                    $errorCodes[] = $message['code'];
                    $errorMessages[] = $message['description'];
                }
            }
        }

        return $this->createResult($isValid, $errorMessages, $errorCodes);
    }
}

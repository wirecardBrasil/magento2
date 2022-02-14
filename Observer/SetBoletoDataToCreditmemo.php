<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Moip\Magento2\Block\Adminhtml\Sales\Creditmemo as CreditmemoBlock;
use Moip\Magento2\Model\Ui\ConfigProviderBoleto;

/**
 * Class SetBoletoDataToCreditmemo - Set refund data of boleto.
 */
class SetBoletoDataToCreditmemo implements ObserverInterface
{
    /**
     * Set boleto data to creditmemo before register.
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $input = $observer->getEvent()->getInput();
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        if ($order->getPayment()->getMethodInstance()->getCode() === ConfigProviderBoleto::CODE) {
            $bankNumber = !empty($input[CreditmemoBlock::BANK_NUMBER]) ? $input[CreditmemoBlock::BANK_NUMBER] : null;
            $creditmemo->setData(CreditmemoBlock::BANK_NUMBER, $bankNumber);
            // phpcs:ignore Generic.Files.LineLength
            $agencyNumber = !empty($input[CreditmemoBlock::AGENCY_NUMBER]) ? $input[CreditmemoBlock::AGENCY_NUMBER] : null;
            $creditmemo->setData(CreditmemoBlock::AGENCY_NUMBER, $agencyNumber);
            // phpcs:ignore Generic.Files.LineLength
            $agencyCheckNumber = !empty($input[CreditmemoBlock::AGENCY_CHECK_NUMBER]) ? $input[CreditmemoBlock::AGENCY_CHECK_NUMBER] : null;
            $creditmemo->setData(CreditmemoBlock::AGENCY_CHECK_NUMBER, $agencyCheckNumber);
            // phpcs:ignore Generic.Files.LineLength
            $accountNumber = !empty($input[CreditmemoBlock::ACCOUNT_NUMBER]) ? $input[CreditmemoBlock::ACCOUNT_NUMBER] : null;
            $creditmemo->setData(CreditmemoBlock::ACCOUNT_NUMBER, $accountNumber);
            // phpcs:ignore Generic.Files.LineLength
            $accountCheckNumber = !empty($input[CreditmemoBlock::ACCOUNT_CHECK_NUMBER]) ? $input[CreditmemoBlock::ACCOUNT_CHECK_NUMBER] : null;
            $creditmemo->setData(CreditmemoBlock::ACCOUNT_CHECK_NUMBER, $accountCheckNumber);
            // phpcs:ignore Generic.Files.LineLength
            $holderFullname = !empty($input[CreditmemoBlock::HOLDER_FULLNAME]) ? $input[CreditmemoBlock::HOLDER_FULLNAME] : null;
            $creditmemo->setData(CreditmemoBlock::HOLDER_FULLNAME, $holderFullname);
            // phpcs:ignore Generic.Files.LineLength
            $holderDocumment = !empty($input[CreditmemoBlock::HOLDER_DOCUMENT_NUMBER]) ? $input[CreditmemoBlock::HOLDER_DOCUMENT_NUMBER] : null;
            $creditmemo->setData(CreditmemoBlock::HOLDER_DOCUMENT_NUMBER, $holderDocumment);
            // phpcs:ignore Generic.Files.LineLength
            $comment = !empty($input[CreditmemoBlock::CREDITMEMO_COMMENT_TEXT]) ? $input[CreditmemoBlock::CREDITMEMO_COMMENT_TEXT] : null;
            // phpcs:ignore Generic.Files.LineLength
            $comment = $comment.'\n'.__('Refund Request to Bank %1, Agency Number %2, Agency Check Number %3, Account Number %4, Account Check Number %5, Holder Name %6, Holder Tax Document %7', $bankNumber, $agencyNumber, $agencyCheckNumber, $accountNumber, $accountCheckNumber, $holderFullname, $holderDocumment);
            $creditmemo->setComment($comment);
        }

        return $this;
    }
}

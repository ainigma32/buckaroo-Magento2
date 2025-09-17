<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */

namespace Buckaroo\Magento2\Model\Method;

class Trustly extends AbstractMethod
{
    /**
     * Payment Code
     */
    const PAYMENT_METHOD_CODE = 'buckaroo_magento2_trustly';

    /**
     * @var string
     */
    public $buckarooPaymentMethodCode = 'trustly';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CODE;

    /**
     * {@inheritdoc}
     */
    public function getOrderTransactionBuilder($payment)
    {
        $transactionBuilder = $this->transactionBuilderFactory->get('order');

        $billingAddress = $payment->getOrder()->getBillingAddress();

        $services = [
            'Name'             => 'Trustly',
            'Action'           => $this->getPayRemainder($payment, $transactionBuilder),
            'Version'          => 1,
            'RequestParameter' => [
                [
                    '_' => $billingAddress->getCountryId(),
                    'Name' => 'CustomerCountryCode',
                ],
                [
                    '_' => $billingAddress->getFirstname(),
                    'Name' => 'CustomerFirstName',
                ],
                [
                    '_' => $billingAddress->getLastName(),
                    'Name' => 'CustomerLastName',
                ],
                [
                    '_' => $billingAddress->getEmail(),
                    'Name' => 'ConsumerEmail',
                ],            
            ],
        ];

        /**
         * @noinspection PhpUndefinedMethodInspection
         */
        $transactionBuilder->setOrder($payment->getOrder())
            ->setServices($services)
            ->setMethod('TransactionRequest');

        return $transactionBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getCaptureTransactionBuilder($payment)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizeTransactionBuilder($payment)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVoidTransactionBuilder($payment)
    {
        return true;
    }

    /**
     * Failure message from failed Trustly Transactions
     *
     * {@inheritdoc}
     */
    protected function getFailureMessageFromMethod($transactionResponse)
    {
        $methodMessage = '';
        $responseCode = $transactionResponse->Status->Code->Code;
        if ($responseCode == 491) {
            if (!empty($transactionResponse->RequestErrors->ParameterError->Name)
                &&
                ($transactionResponse->RequestErrors->ParameterError->Name == 'CustomerCountryCode')
                &&
                !empty($transactionResponse->RequestErrors->ParameterError->Error)
                &&
                ($transactionResponse->RequestErrors->ParameterError->Error == 'ParameterInvalid')
                &&
                !empty($transactionResponse->RequestErrors->ParameterError->_)
            ) {
                $methodMessage = $transactionResponse->RequestErrors->ParameterError->_;
            }
        }

        return $methodMessage;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface|\Magento\Payment\Model\InfoInterface $payment
     *
     * @return bool|string
     */
    public function getPaymentMethodName($payment)
    {
        return $this->buckarooPaymentMethodCode;
    }
}

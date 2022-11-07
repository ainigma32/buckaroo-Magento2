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

namespace Buckaroo\Magento2\Plugin\Method;

class SepaDirectDebit
{
    /**
     * @var BuckarooAdapter
     */
    private BuckarooAdapter $paymentMethod;

    /**
     * @param BuckarooAdapter $paymentMethod
     */
    public function __construct(BuckarooAdapter $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @param BuckarooAdapter $paymentMethod
     * @param array|\StdCLass                            $response
     *
     * @return $this
     */
    public function afterOrderTransaction(
        BuckarooAdapter $paymentMethod,
        $response
    ) {
        if (!empty($response[0]->ConsumerMessage) && $response[0]->ConsumerMessage->MustRead == 1) {
            $consumerMessage = $response[0]->ConsumerMessage;

            $payment->messageManager->addSuccessMessage(
                __($consumerMessage->Title)
            );
            $payment->messageManager->addSuccessMessage(
                __($consumerMessage->PlainText)
            );
        }

        return $response;
    }
}

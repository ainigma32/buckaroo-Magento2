<?php
// phpcs:ignoreFile
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
namespace Buckaroo\Magento2\Test\Unit\Model\ConfigProvider\Method;



use Buckaroo\Magento2\Model\ConfigProvider\Method\AbstractConfigProvider;
use Magento\Store\Model\ScopeInterface;
use Buckaroo\Magento2\Helper\PaymentFee;
use Buckaroo\Magento2\Test\BaseTest;
use Buckaroo\Magento2\Model\ConfigProvider\Method\Afterpay20;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class Afterpay20Test extends BaseTest
{
    protected $instanceClass = Afterpay20::class;

    public static function getConfigProvider()
    {
        return [
            'active' => [
                true,
                [
                    'payment' => [
                        'buckaroo' => [
                            'afterpay20' => [
                                'sendEmail' => '1',
                                'paymentFeeLabel' => 'Payment Fee',
                                'allowedCurrencies' => ['EUR']
                            ],
                            'response' => []
                        ]
                    ]
                ]
            ],
            'inactive' => [
                false,
                []
            ]
        ];
    }

    /**
     * @param $active
     * @param $expected
     *
     * @dataProvider getConfigProvider
     */
    public function testGetConfig($active, $expected)
    {
        $scopeConfigMock = $this->getFakeMock(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->getMockForAbstractClass();
        // PHPUnit 10: use a value map instead of withConsecutive()
        $valueMap = [
            [
                $this->getPaymentMethodConfigPath(Afterpay20::CODE, AbstractConfigProvider::ACTIVE),
                ScopeInterface::SCOPE_STORE,
                null,
                $active
            ],
            [
                $this->getPaymentMethodConfigPath(Afterpay20::CODE, AbstractConfigProvider::ORDER_EMAIL),
                ScopeInterface::SCOPE_STORE,
                null,
                '1'
            ],
            [
                $this->getPaymentMethodConfigPath(Afterpay20::CODE, AbstractConfigProvider::ALLOWED_CURRENCIES),
                ScopeInterface::SCOPE_STORE,
                null,
                'EUR'
            ]
        ];

        $scopeConfigMock->method('getValue')
            ->willReturnMap($valueMap);

        $paymentFeeMock = $this->getFakeMock(PaymentFee::class)->onlyMethods(['getBuckarooPaymentFeeLabel'])->getMock();
        $paymentFeeMock->method('getBuckarooPaymentFeeLabel')->willReturn('Fee');

        $instance = $this->getInstance(['scopeConfig' => $scopeConfigMock, 'paymentFeeHelper' => $paymentFeeMock]);
        $result = $instance->getConfig();

        if ($active) {
            $this->assertArrayHasKey('buckaroo_magento2_afterpay20', $result['payment']['buckaroo']);
        } else {
            $this->assertEquals($expected, $result);
        }
    }

    public static function getPaymentFeeProvider()
    {
        return [
            'null value' => [
                null,
                false
            ],
            'false value' => [
                false,
                false
            ],
            'empty int value' => [
                0,
                false
            ],
            'empty float value' => [
                0.00,
                false
            ],
            'empty string value' => [
                '',
                false
            ],
            'int value' => [
                '1',
                1
            ],
            'float value' => [
                2.34,
                2.34
            ],
            'string value' => [
                '5.67',
                5.67
            ],
        ];
    }

    /**
     * @param $value
     * @param $expected
     *
     * @dataProvider getPaymentFeeProvider
     */
    public function testGetPaymentFee($value, $expected)
    {
        $scopeConfigMock = $this->getFakeMock(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->getMockForAbstractClass();
        $scopeConfigMock->method('getValue')
            ->with(
                $this->getPaymentMethodConfigPath(Afterpay20::CODE, AbstractConfigProvider::PAYMENT_FEE),
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn($value);

        $instance = $this->getInstance(['scopeConfig' => $scopeConfigMock]);
        $result = $instance->getPaymentFee();

        $this->assertEquals($expected, $result);
    }
}

<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please email
 * to support@buckaroo.nl, so we can send you a copy immediately.
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

namespace Buckaroo\Magento2\Controller\Pos;

use Buckaroo\Magento2\Exception;
use Buckaroo\Magento2\Model\BuckarooStatusCode;
use Buckaroo\Magento2\Model\ConfigProvider\Factory;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckOrderStatus extends Action implements HttpPostActionInterface
{
    /**
     * @var Order $order
     */
    protected Order $order;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @var ConfigProviderInterface
     */
    protected ConfigProviderInterface $accountConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var FormKey
     */
    private FormKey $formKey;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @param Context $context
     * @param Order $order
     * @param JsonFactory $resultJsonFactory
     * @param Factory $configProviderFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param FormKey $formKey
     * @param Session $customerSession
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Order $order,
        JsonFactory $resultJsonFactory,
        Factory $configProviderFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        FormKey $formKey,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->order = $order;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->accountConfig = $configProviderFactory->get('account');
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->formKey = $formKey;
        $this->customerSession = $customerSession;
    }

    /**
     * Process action
     *
     * @return Json
     * @throws \Exception
     */
    public function execute()
    {
        $response = ['success' => 'false', 'redirect' => ''];

        if (($params = $this->getRequest()->getParams()) && !empty($params['orderId'])) {
            $this->order->loadByIncrementId($params['orderId']);
            if ($this->customerSession->getCustomerId() === $this->order->getCustomerId() && $this->order->getId()) {
                $store = $this->order->getStore();
                $url = '';

                if (in_array($this->order->getState(), ['processing', 'complete'])) {
                    $url = $store->getBaseUrl() . '/' . $this->accountConfig->getSuccessRedirect($store);
                }

                if (in_array($this->order->getState(), ['canceled', 'closed'])) {
                    $returnUrl = $this->urlBuilder->setScope($this->storeManager->getStore()->getStoreId());
                    $url = $returnUrl->getRouteUrl('buckaroo/redirect/process')
                        . '?form_key=' . $this->formKey->getFormKey();
                    $extraData = [
                        'brq_invoicenumber' => $params['orderId'],
                        'brq_ordernumber'   => $params['orderId'],
                        'brq_statuscode'    => BuckarooStatusCode::ORDER_FAILED,
                    ];

                    $url = $url . '&' . http_build_query($extraData);
                }

                $response = ['success' => 'true', 'redirect' => $url];
            }
        }

        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}

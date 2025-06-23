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
namespace Buckaroo\Magento2\Cron;

class SecondChancePrune
{
    /**
     * @var \Buckaroo\Magento2\Model\ConfigProvider\SecondChance
     */
    protected $configProvider;

    /**
     * @var \Buckaroo\Magento2\Logging\Log
     */
    public $logging;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var \Buckaroo\Magento2\Model\SecondChanceRepository
     */
    protected $secondChanceRepository;

    /**
     * @param \Buckaroo\Magento2\Model\ConfigProvider\SecondChance $configProvider
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \Buckaroo\Magento2\Logging\Log $logging
     * @param \Buckaroo\Magento2\Model\SecondChanceRepository $secondChanceRepository
     */
    public function __construct(
        \Buckaroo\Magento2\Model\ConfigProvider\SecondChance $configProvider,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Buckaroo\Magento2\Logging\Log $logging,
        \Buckaroo\Magento2\Model\SecondChanceRepository $secondChanceRepository
    ) {
        $this->configProvider         = $configProvider;
        $this->storeRepository        = $storeRepository;
        $this->logging                = $logging;
        $this->secondChanceRepository = $secondChanceRepository;
    }

    /**
     * Execute cron job to prune old SecondChance records
     */
    public function execute()
    {
        $this->logging->addDebug(__METHOD__ . '|Starting SecondChance prune execution');
        
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            if ($this->configProvider->isSecondChanceEnabled($store)) {
                $this->logging->addDebug(__METHOD__ . '|Pruning old records for store: ' . $store->getId());
                
                try {
                    $this->secondChanceRepository->deleteOlderRecords($store);
                } catch (\Exception $e) {
                    $this->logging->addError('Error pruning SecondChance records for store ' . $store->getId() . ': ' . $e->getMessage());
                }
            }
        }
        
        $this->logging->addDebug(__METHOD__ . '|SecondChance prune execution completed');
        return $this;
    }
}

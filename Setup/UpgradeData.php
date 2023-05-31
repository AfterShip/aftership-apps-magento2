<?php

namespace AfterShip\Tracking\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Integration\Api\IntegrationServiceInterface;

class UpgradeData implements UpgradeDataInterface
{
    use IntegrationTrait;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * Integration service
     *
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * @var AuthorizationServiceInterface $authorizationService
     */
    protected $authorizationService;

    public function __construct(
        StoreRepositoryInterface      $storeRepository,
        IntegrationServiceInterface   $integrationService,
        AuthorizationServiceInterface $authorizationService
    )
    {
        $this->storeRepository = $storeRepository;
        $this->integrationService = $integrationService;
        $this->authorizationService = $authorizationService;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->run($setup, $context);
    }
}

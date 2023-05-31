<?php

namespace AfterShip\Tracking\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class InstallData implements InstallDataInterface
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

    /**
     * {@inheritdoc}
     */

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->run($setup, $context);
    }
}

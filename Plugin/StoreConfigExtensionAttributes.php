<?php

namespace AfterShip\Tracking\Plugin;

use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Store\Api\Data\StoreConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Store\Api\Data\StoreConfigExtensionFactory;

class StoreConfigExtensionAttributes
{
    private $userContext;
    private $integrationService;
    private $storeConfigExtensionFactory;

    public function __construct(
        StoreConfigExtensionFactory $storeConfigExtensionFactory,
        UserContextInterface        $userContext,
        IntegrationServiceInterface $integrationService
    )
    {

        $this->storeConfigExtensionFactory = $storeConfigExtensionFactory;
        $this->userContext = $userContext;
        $this->integrationService = $integrationService;
    }

    private function getApiScopes()
    {
        $integrationId = $this->userContext->getUserId();
        $apiScopes = '';
        if ($integrationId) {
            $scopes = $this->integrationService->getSelectedResources($integrationId);
            $apiScopes = is_array($scopes) ? implode(',', $scopes) : $scopes;
        }
        return $apiScopes;
    }

    public function afterGetStoreConfigs(StoreConfigManagerInterface $subject, $result)
    {
        /** @var StoreConfigInterface $store */
        foreach ($result as $store) {
            $extensionAttributes = $store->getExtensionAttributes();
            if (!$extensionAttributes) {
                $extensionAttributes = $this->storeConfigExtensionFactory->create();
            }
            $extensionAttributes->setData('permissions', $this->getApiScopes());
            $store->setExtensionAttributes($extensionAttributes);
        }
        return $result;
    }
}

<?php

namespace AfterShip\Tracking\Plugin;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\RequestInterface;

class StockItemsPlugin
{

    /**
     * RequestInterface instance
     *
     * @var RequestInterface $request
     */
    protected $request;
    /**
     * StockItemRepositoryInterface instance
     *
     * @var StockItemRepositoryInterface $stockItemRepository
     */
    protected $stockItemRepository;
    /**
     * StockItemCriteriaInterfaceFactory instance
     *
     * @var StockItemCriteriaInterfaceFactory $criteriaFactory
     */
    protected $criteriaFactory;


    /**
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     */
    public function __construct(
        RequestInterface                  $request,
        StockItemRepositoryInterface      $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory

    )
    {
        $this->request = $request;
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
    }

    /**
     * @param StockRegistryInterface $subject
     * @param \Closure $proceed
     * @param int $scopeId
     * @param float $qty
     * @param int $currentPage
     * @param int $pageSize
     * @return StockItemInterface[]
     */
    public function aroundGetLowStockItems(
        StockRegistryInterface $subject,
        \Closure               $proceed,
                               $scopeId,
                               $qty,
                               $currentPage = 1,
                               $pageSize = 0
    )
    {
        $productIds = $this->request->getParam('productIds');
        if (empty($productIds)) {
            return $proceed($scopeId, $qty, $currentPage, $pageSize);
        }
        if (is_array($productIds) && count($productIds) > 0) {
            $productIds = array_map('intval', $productIds);
        } else {
            $productIds = array_map('intval', explode(',', $productIds));
        }
        $criteria = $this->criteriaFactory->create();
        $criteria->setLimit($currentPage, $pageSize);
        $criteria->setScopeFilter(0);
        $criteria->setProductsFilter($productIds);
        return $this->stockItemRepository->getList($criteria);
    }
}

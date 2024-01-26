<?php

namespace AfterShip\Tracking\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

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
     * CollectionFactory instance
     *
     * @var CollectionFactory $productCollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * Logger Instance.
     *
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * StockItemsPlugin constructor.
     *
     * @param RequestInterface $request
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     * @param CollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface                  $request,
        StockItemRepositoryInterface      $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory,
        CollectionFactory                 $productCollectionFactory,
        LoggerInterface                   $logger
    )
    {
        $this->request = $request;
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
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
        try {
            $productIds = $this->request->getParam('productIds');
            if (!empty($productIds)) {
                return $this->getStockItemsByProductIds($productIds, $currentPage, $pageSize);
            }
            $skus = $this->request->getParam('skus');
            if (!empty($skus)) {
                return $this->getStockItemsBySkus($skus, $currentPage, $pageSize);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip Tracking] Failed to get stockItems by products, %s',
                    $e->getMessage()
                )
            );
        }
        return $proceed($scopeId, $qty, $currentPage, $pageSize);
    }

    /**
     * get stock items by skus.
     *
     * @param string|string[] $skus
     * @param int $currentPage
     * @param int $pageSize
     * @return StockItemInterface[]
     */
    private function getStockItemsBySkus($skus, $currentPage, $pageSize)
    {
        if (is_array($skus)) {
            $skus = array_map('strval', $skus);
        } else {
            $skus = array_map('strval', explode(',', $skus));
        }
        /** @var $productCollection Collection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter('sku', ['in' => $skus])
            ->addAttributeToSelect('entity_id');
        $productIds = $productCollection->getColumnValues('entity_id');
        $criteria = $this->criteriaFactory->create();
        $criteria->setLimit($currentPage, $pageSize);
        $criteria->setScopeFilter(0);
        $criteria->setProductsFilter($productIds);
        return $this->stockItemRepository->getList($criteria);
    }

    /**
     * get stock items by product ids.
     *
     * @param string|string[] $productIds
     * @param int $currentPage
     * @param int $pageSize
     * @return StockItemInterface[]
     */
    private function getStockItemsByProductIds($productIds, $currentPage, $pageSize)
    {
        if (is_array($productIds)) {
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

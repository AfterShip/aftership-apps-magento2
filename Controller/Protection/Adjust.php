<?php

namespace AfterShip\Tracking\Controller\Protection;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;
use AfterShip\Tracking\Constants;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Exception;
use Throwable;

class Adjust extends Action
{
    protected $cart;
    protected $assetRepository;
    protected $storeManager;
    protected $productInterfaceFactory;
    protected $productRepository;
    protected $directoryList;
    protected $file;


    /**
     * Inject dependencies
     *
     * @param Context $context
     * @param Cart $cart
     * @param AssetRepository $assetRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param DirectoryList $directoryList
     * @param File $file
     * @see http://www.example.com/aftership/protection/adjust
     */
    public function __construct(
        Context                    $context,
        Cart                       $cart,
        AssetRepository            $assetRepository,
        StoreManagerInterface      $storeManager,
        ProductInterfaceFactory    $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        DirectoryList              $directoryList,
        File                       $file
    )
    {
        parent::__construct($context);
        $this->cart = $cart;
        $this->assetRepository = $assetRepository;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Add protection product to cart
     *
     * @return void
     */
    public function execute()
    {
        try {
            $newPrice = abs($this->getRequest()->getParam('price'));
            $product = $this->getProtectionProduct();
            $this->cart->addProduct($product, [
                'product' => $product->getId(),
                'qty' => 1
            ]);
            $quote = $this->cart->getQuote();
            $quote->getItemByProduct($product)
                ->setQty(1)
                ->setBaseCost($newPrice)
                ->setPrice($newPrice)
                ->setPriceInclTax($newPrice)
                ->setBasePrice($newPrice)
                ->setBasePriceInclTax($newPrice)
                ->setCustomPrice($newPrice)
                ->setOriginalCustomPrice($newPrice)
                ->setRowTotal($newPrice)
                ->setRowTotalInclTax($newPrice)
                ->setRowTotalWithDiscount($newPrice)
                ->setBaseRowTotal($newPrice)
                ->setBaseRowTotalInclTax($newPrice)
                ->setTaxAmount(0)
                ->setBaseTaxAmount(0)
                ->setBaseTaxBeforeDiscount(0)
                ->setTaxBeforeDiscount(0)
                ->getProduct()
                ->setIsSuperMode(true)
                ->save();
            $quote
                ->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
            $result = [
                'price' => $newPrice,
                'success' => true
            ];
        } catch (Throwable $t) {
            $this->getResponse()->setHttpResponseCode(400);
            $result = [
                'success' => false,
                'detail' => 'failed to create protection product',
                'error_message' => $t->getMessage(),
                'error_trace' => $t->getTraceAsString(),
            ];
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $result = [
                'success' => false,
                'detail' => 'failed to create protection product',
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ];
        }
        return $this->getResponse()->representJson(json_encode($result));
    }

    /**
     * Get full path for protection image on plugin folder
     *
     * @return bool|string|null
     */
    public function getProtectionImageFullPath()
    {
        $fileId = 'AfterShip_Tracking::images/aftership_protection.png';
        $params = [
            'area' => 'frontend'
        ];
        $asset = $this->assetRepository->createAsset($fileId, $params);
        return $asset->getSourceFile();
    }

    /**
     * Get protection product
     *
     * @return ProductInterface|void
     */
    protected function getProtectionProduct()
    {
        try {
            return $this->productRepository->get(Constants::AFTERSHIP_PROTECTION_SKU);
        } catch (Exception $e) {
            return $this->createProtectionProduct();
        }
    }

    /**
     * Copy protection image from plugin to media directory
     *
     * @param $sourcePath
     * @param $fileName
     * @return string
     */
    protected function copyImageToMediaDirectory($sourcePath, $fileName)
    {
        $mediaDirectory = $this->directoryList->getPath(DirectoryList::MEDIA);
        $destinationPath = $mediaDirectory . '/' . $fileName;
        $this->file->cp($sourcePath, $destinationPath);
        return $destinationPath;
    }

    /**
     * Create protection product
     *
     * @return void
     */
    protected function createProtectionProduct()
    {
        $productData = [
            'sku' => Constants::AFTERSHIP_PROTECTION_SKU,
            'name' => 'AfterShip Protection',
            'description' => 'Shipping Protection provided by AfterShip',
            'price' => 0.01,
            'status' => Status::STATUS_ENABLED,
            'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
            'type_id' => Type::TYPE_VIRTUAL,
            'attribute_set_id' => 4,
            'tax_class_id' => 0,
            'website_ids' => $this->getAllSiteIds(),
        ];
        $product = $this->productInterfaceFactory->create();
        $product->setData($productData);
        $product->setStockData([
            'is_in_stock' => true,
            'backorders' => true,
            'qty' => 0,
            'use_config_backorders' => false,
            'use_config_manage_stock' => false
        ]);
        $imagePath = $this->getProtectionImageFullPath();
        $mediaPath = $this->copyImageToMediaDirectory($imagePath, 'aftership_protection.png');
        if ($mediaPath) {
            $product->addImageToMediaGallery($mediaPath, ['image', 'thumbnail', 'small_image'], true, false);
        }
        $product->save();
        return $product;
    }

    /**
     * Get all site ids
     *
     * @return array
     */
    protected function getAllSiteIds()
    {
        $websites = $this->storeManager->getWebsites();
        $siteIds = [];
        foreach ($websites as $website) {
            $siteIds[] = $website->getId();
        }
        return $siteIds;
    }

}

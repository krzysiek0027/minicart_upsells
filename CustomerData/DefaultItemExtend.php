<?php

namespace Kw\MinicartUpsells\CustomerData;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Checkout\CustomerData\DefaultItem as DefaultItemCore;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Helper\Data;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Helper\Cart;

class DefaultItemExtend extends DefaultItemCore
{

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var Cart
     */
    protected Cart $cartHelper;

    /**
     * @param Image $imageHelper
     * @param \Magento\Msrp\Helper\Data $msrpHelper
     * @param UrlInterface $urlBuilder
     * @param ConfigurationPool $configurationPool
     * @param Data $checkoutHelper
     * @param Escaper|null $escaper
     * @param ItemResolverInterface|null $itemResolver
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Image $imageHelper,
        \Magento\Msrp\Helper\Data $msrpHelper,
        UrlInterface $urlBuilder,
        ConfigurationPool $configurationPool,
        Data $checkoutHelper,
        Escaper $escaper = null,
        ItemResolverInterface $itemResolver = null,
        ProductRepositoryInterface $productRepository,
        Cart $cartHelper
    ) {
        $this->productRepository = $productRepository;
        $this->cartHelper = $cartHelper;
        parent::__construct($imageHelper, $msrpHelper, $urlBuilder, $configurationPool, $checkoutHelper, $escaper, $itemResolver);
    }

    /**
     * Extend core doGetItemData by Upsell Products data for each cart item
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function doGetItemData() {
        $cartItemData = parent::doGetItemData();

        $cartItemData['upsell_products'] = $this->getUpSellProducts($this->item->getProduct()->getUpSellProductIds());

        return $cartItemData;
    }

    /**
     * @param $upSellProductIds
     * @return array
     */
    protected function getUpSellProducts($upSellProductIds):array {
        $upsellProducts = [];
        foreach ($upSellProductIds as $upsellProductId) {
            try {
                $upsellProduct = $this->productRepository->getById($upsellProductId);
                $upsellProduct['add_to_cart_url'] = $this->cartHelper->getAddUrl($upsellProduct);
                $upsellProduct['url'] = $upsellProduct->getProductUrl();
                $upsellProduct['final_price'] = $upsellProduct->getFormattedPrice();
                $upsellProduct['thumbnail_url'] = $this->imageHelper->init($upsellProduct, 'mini_cart_product_thumbnail')
                    ->setImageFile($upsellProduct->getThumbnail())
                    ->getUrl();
                array_push($upsellProducts, $upsellProduct->getData());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            }
        }

        return $upsellProducts;
    }
}

<?php
/**
 * GiaPhuGroup Co., Ltd.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GiaPhuGroup.com license that is
 * available through the world-wide-web at this URL:
 * https://www.giaphugroup.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PHPCuong
 * @package     PHPCuong_UrlRewrite
 * @copyright   Copyright (c) 2018-2019 GiaPhuGroup Co., Ltd. All rights reserved. (http://www.giaphugroup.com/)
 * @license     https://www.giaphugroup.com/LICENSE.txt
 */

namespace PHPCuong\UrlRewrite\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductRebuildUrlRewrite extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGeneratorFactory
     */
    protected $productUrlRewriteGeneratorFactory;

    /**
     * @var \Magento\UrlRewrite\Model\UrlPersistInterface
     */
    protected $urlPersist;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGeneratorFactory $productUrlRewriteGeneratorFactory
     * @param \Magento\UrlRewrite\Model\UrlPersistInterface $urlPersist
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGeneratorFactory $productUrlRewriteGeneratorFactory,
        \Magento\UrlRewrite\Model\UrlPersistInterface $urlPersist
    ) {
        $this->appState = $appState;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->productUrlRewriteGeneratorFactory = $productUrlRewriteGeneratorFactory;
        $this->urlPersist = $urlPersist;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        // Set the name and description for the new CLI command
        $this->setName('catalog:product:urls:rebuild')
            ->setDescription('Rebuild the URLs Rewrite for products');
    }

    /**
     * Execute the codes to generate the product URLs
     *
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // We must set the area code
        $this->appState->setAreaCode('catalog');
        // Get all the stores view on your website
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $output->writeln('<info>Starting generates the product URLs for the store ID:'.$store->getId().'</info>');
            // Get all the products for generating URLs, only the products visible
            $productCollection = $this->productFactory->create()->getCollection()->setStoreId(
                $store->getId()
            )->addAttributeToSelect(
                '*'
            )->addAttributeToFilter(
                // the neq is not equals "1"
                'visibility', ['neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE]
            );
            // Loop all products
            foreach ($productCollection as $product) {
                $output->writeln('<info>The product url key: '.$product->getUrlKey().'</info>');
                // Get the product category ids for generating product urls has the url key of category in the path.
                $productCategoryIds = $product->getCategoryIds();
                foreach ($productCategoryIds as $categoryId) {
                    $this->urlPersist->replace($this->productUrlRewriteGeneratorFactory->create()->generate($product, $categoryId));
                }
            }
            $output->writeln('<info>The End.</info>');
        }
        $output->writeln('<info>Rebuilding the URLs for products successfully.</info>');
    }
}

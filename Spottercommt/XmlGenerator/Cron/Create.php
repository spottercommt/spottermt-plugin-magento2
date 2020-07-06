<?php

namespace Spottercommt\XmlGenerator\Cron;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use \Psr\Log\LoggerInterface;
use Spottercommt\XmlGenerator\Cron\XMLGenerator;

class Create extends \Magento\Framework\App\Action\Action
{

    protected $logger;
    protected $_pageFactory;
    /**
     * Product collection
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $collection;

    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        LoggerInterface $logger)
    {
        $this->collection = $collectionFactory;//->create();
        $this->_pageFactory = $pageFactory;
        $this->logger = $logger;
        return parent::__construct($context);
    }

    /**
     * Write to system.log
     *
     * @return void
     */

    public function execute()
    {

//        $this->logger->info('XML Started');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();

        $collection = $this->collection->create();
        $collection->addAttributeToSelect('*');
//        $collection->addAttributeToSelect('name');
//        $collection->addAttributeToSelect('price');
//        $collection->setPageSize(2); // fetching only 3 products
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->load();

        $xml = new XMLGenerator('<?xml version="1.0" encoding="utf-8"?><webstore/>');
        $now = date('Y-m-d H:i:s');
        $xml->addChild('created_at', "$now");
        $products = $xml->addChild('products');
        foreach ($collection as $product) {
            $categoryFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');

            $categories = $categoryFactory->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id', $product->getCategoryIds())->setOrder('position', 'ASC');
            $cat = [];
            foreach ($categories as $category) {
                $cat[] = $category->getName();
            }
            $finalCat =  implode(' > ', $cat);
            $productxml = $products->addChild('product');
            $productxml->sku = null;
            $productxml->sku->addCData($product['sku']);
            $productxml->url = null;
            $productxml->url->addCData($product->getProductUrl());
            $productxml->name = null;
            $productxml->name->addCData($product['name']);
//            $productxml->manufacturer = null;
//            $productxml->manufacturer->addCData($manufacturer_name);
            $productxml->category = null;
            $productxml->category->addCData($finalCat);
            $productxml->price_with_vat = null;
            $productxml->price_with_vat->addCData($product['price']);
            $productxml->image = null;
            $productxml->image->addCData($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage());
            $productxml->description = null;
        }
        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        $rootPath = $directory->getRoot();
        $xml->saveXML($rootPath . '/spotter.xml');
//        $this->logger->info('XML Finished');


    }
}

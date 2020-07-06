<?php

namespace Spottercommt\XmlGenerator\Controller\Index;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Spottercommt\XmlGenerator\Controller\Index\XMLGenerator;

//class Test extends \Magento\Framework\App\Action\Action
class Create extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_productCollectionFactory;
    private $storeManager;
    /**
     * Product collection
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $collection;

    public function __construct(
//        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
//        \Magento\Framework\Filesystem\DirectoryList $dir,
//        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory)
    {
        $this->collection = $collectionFactory;//->create();
        $this->_pageFactory = $pageFactory;
//        $this->storeManager = $storeManager;
//        print_r(get_class_methods($context));
        return parent::__construct($context);
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
//        $store = $this->storeManager->getStore();

        $collection = $this->collection->create();
        $collection->addAttributeToSelect('*');
//        $collection->addAttributeToSelect('name');
//        $collection->addAttributeToSelect('price');
        $collection->setPageSize(2); // fetching only 3 products
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->load();
//        return $this->collection;
//        $a = 1;
//        echo "Count:";
//        var_dump($collection);
//        echo $collection->count();
//        echo "<br>";
        /*        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><webstore/>');*/
        $xml = new XMLGenerator('<?xml version="1.0" encoding="utf-8"?><webstore/>');
        $now = date('Y-m-d H:i:s');
        $xml->addChild('created_at', "$now");
        $products = $xml->addChild('products');
        foreach ($collection as $product) {
            $categoryFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');

            $categories = $categoryFactory->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id', $product->getCategoryIds())->setOrder('position', 'ASC');
//var_dump($categories);
            $cat = [];
            foreach ($categories as $category) {
                $cat[] = $category->getName();
            }
//            implode(',', $cat);
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
            $productxml->description->addCData($product['description']);


//            echo "<br>";
//            $productUrl =  $product->getProductUrl();
//            $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
//            var_dump($product->getCategoryIds());
//            var_dump($productUrl);
//            var_dump($imageUrl);
//            echo "<br>";
//
//            echo '<textarea cols="200" rows="10">' . json_encode($product->getData()) . '</textarea>';
//            echo '<textarea cols="200" rows="10">' . $xml->asXML() . '</textarea>';
        }
        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        $rootPath = $directory->getRoot();
        $xml->saveXML($rootPath . '/spotter.xml');
        echo 'Done';
        exit;
    }
}

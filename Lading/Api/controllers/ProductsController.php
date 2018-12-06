<?php

/**
 * Class Lading_Api_ProductsController
 */
class Lading_Api_ProductsController extends Mage_Core_Controller_Front_Action {


    /**
     * 获取商品自定义属性
     */

    public function init() {
        $session = Mage::getSingleton('customer/session');
        $customerId =  $this->getRequest()->getParam('cust_id'); 
        if($customerId){
        try{
            if ($session->isLoggedIn()) {
                if($session->getCustomer()->getId() != $customerId ){
                    $session->logout();
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    $session->setCustomerAsLoggedIn($customer);
                }
            }else{
                $customer = Mage::getModel('customer/customer')->load($customerId);
                $session->setCustomerAsLoggedIn($customer);
            }
        }
        catch (Exception $e){
            echo json_encode ( array (
                            'code'=>1,
                            'msg'=>$errors,
                            'model'=>array () 
                    ) );
        }}
    }
    /*********For Testing Purpose only **********/
    public function getCustomOptionAction() {
        $baseCurrency = Mage::app ()->getStore ()->getBaseCurrency ()->getCode ();
        $currentCurrency = Mage::app ()->getStore ()->getCurrentCurrencyCode ();
        $product_id = $this->getRequest ()->getParam ( 'product_id' );
        $product = Mage::getModel ( "catalog/product" )->load ( $product_id );
        $selectid = 1;
        $select = array ();
        foreach ( $product->getOptions () as $o ) {
            if (($o->getType () == "field") || ($o->getType () =="file")) {
                $select [$selectid] = array (
                    'option_id' => $o->getId (),
                    'custom_option_type' => $o->getType (),
                    'custom_option_title' => $o->getTitle (),
                    'is_require' => $o->getIsRequire (),
                    'price' => number_format ( Mage::helper ( 'directory' )->currencyConvert ( $o->getPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' ),
                    'price_type'=>$o->getPriceType(),
                    'sku'=>$o->getSku(),
                    'max_characters' => $o->getMaxCharacters (),
                );
            } else {
                $max_characters = $o->getMaxCharacters ();
                $optionid = 1;
                $options = array ();
                $values = $o->getValues ();
                foreach ( $values as $v ) {
                    $options [$optionid] = $v->getData ();
                    $optionid ++;
                }
                $select [$selectid] = array (
                    'option_id' => $o->getId (),
                    'custom_option_type' => $o->getType (),
                    'custom_option_title' => $o->getTitle (),
                    'is_require' => $o->getIsRequire (),
                    'price' => number_format ( Mage::helper ( 'directory' )->currencyConvert ( $o->getFormatedPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' ),
                    'max_characters' => $max_characters,
                    'custom_option_value' => $options
                );
            }
            $selectid ++;
        }
        echo json_encode ( array('code'=>0, 'msg'=>null, 'model'=>$select) );
    }
    /*********End For Testing Purpose only **********/
    
    public function getProductAttributeOptions($product){
        $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        $attributeOptions = array();
        foreach ($productAttributeOptions as $productAttribute) {
            foreach ($productAttribute['values'] as $attribute) {
                $attributeOptions[$productAttribute['label']][$attribute['value_index']] = $attribute['store_label'];
            }
        }
        return  $productAttributeOptions;
    }


    /**
     * 获取商品详情
     */
    public function getProductDetailAction() {
        $this->init();
        $baseCurrency = Mage::app ()->getStore ()->getBaseCurrency ()->getCode ();
        $currentCurrency = Mage::app ()->getStore ()->getCurrentCurrencyCode ();
        $product_id = $this->getRequest ()->getParam ( 'product_id' );
        $products_model = Mage::getModel('mobile/products');
        $product = Mage::getModel ( "catalog/product" )->load ( $product_id );
        $store_id = Mage::app()->getStore()->getId();
        $product_detail = array();
        $options = array();
        $price = array();
        $product_type = $product->getTypeId();
        switch($product_type){
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE: {
                $product_detail['attribute_options'] = $products_model->getProductOptions($product);
                $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                $price = number_format($price, 2, '.', '' );
            }break;
            case Mage_Catalog_Model_Product_Type::TYPE_SIMPLE: {
                $product_detail['custom_options'] = $products_model->getProductCustomOptionsOption($product);
                $product_detail['stock_level'] = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
                $price = $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                $price = number_format($price, 2, '.', '' );
            }break;
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE: {
                $price = $products_model->collectBundleProductPrices($product);
                $product_detail['bundle_option']  =  $products_model->getProductBundleOptions($product);
            }break;
            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED: {
                $product_detail['grouped_option']  =  $products_model->getProductGroupedOptions($product);
            }break;
            case Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL:  {
                $price = $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                $price = number_format($price, 2, '.', '' );
            }break;
            default: {
                $price = $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                $price = number_format($price, 2, '.', '' );
            } break;
        }
        $product_detail['price'] = $price;
        $mediaGallery = array();
        foreach($product->getMediaGalleryImages()->getItems() as $image){
            $mediaGallery[] = $image['url'];
        }
        if(count($mediaGallery)<=0){

           array_push($mediaGallery,$product->getImageUrl ());
        };
        $product_detail['in_wishlist'] = false;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer_id =  Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId();
            $item_collection = Mage::getModel('wishlist/item')->getCollection()->addCustomerIdFilter($customer_id);
            foreach($item_collection as $item) {
                if($item->getProductId()==$product->getId ()){
                    $product_detail['in_wishlist'] = true;
                }
            }
        }
        $summaryData = Mage::getModel('review/review_summary')->setStoreId($store_id)  ->load($product->getId());
        $product_detail['cat_id'] = $product->getCategoryIds();
        $product_detail['entity_id'] = $product->getId ();
        $product_detail['rating_summary'] = $summaryData->getRatingSummary();
        $product_detail['reviews_count'] = $summaryData->getReviewsCount();
        $product_detail['sku'] = $product->getSku ();
        $product_detail['status'] = $product->getStatus();
        $product_detail['name'] = $product->getName ();
        $product_detail['news_from_date'] = $product->getNewsFromDate ();
        $product_detail['news_to_date'] = $product->getNewsToDate ();
        $product_detail['product_type'] = $product->getTypeID();
        $product_detail['special_from_date'] = $product->getSpecialFromDate ();
        $product_detail['special_to_date'] = $product->getSpecialToDate ();
        $product_detail['image_url'] = $product->getImageUrl ();
        $product_detail['url_key'] = $product->getProductUrl ();
        $product_detail['regular_price_with_tax'] = number_format(Mage::helper('directory')->currencyConvert($product->getPrice(), $baseCurrency, $currentCurrency), 2, '.', '' );
        $product_detail['final_price_with_tax'] = number_format ( Mage::helper ( 'directory' )->currencyConvert ( $product->getSpecialPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' );
//			'description' => nl2br ( $product->getDescription()),
        $product_detail['short_description'] = $product->getShortDescription();
//        $product_detail['description'] = $product->getDescription();

        $product_detail['description'] = $product->getDescription();                   //add by wayne    /*long description*/
        $product_detail['additional'] = $products_model->getAdditionalFront($product); //add by wayne    /*additional information Visible on Product View Page on Front-end*/
        $product_detail['symbol'] = Mage::app ()->getLocale ()->currency ( Mage::app ()->getStore ()->getCurrentCurrencyCode () )->getSymbol ();
        $product_detail['options'] = $options;
        $product_detail['mediaGallery'] = $mediaGallery;

        echo json_encode(array('code'=>0, 'msg'=>null, 'model'=>$product_detail));
    }
     public function getProductDetailByCategoryAction() {
        $this->init();
        $baseCurrency = Mage::app ()->getStore ()->getBaseCurrency ()->getCode ();
        $currentCurrency = Mage::app ()->getStore ()->getCurrentCurrencyCode ();
        $cat_id = $this->getRequest ()->getParam ( 'category_id' );

        $category = Mage::getModel('catalog/category')->load($cat_id);

        $products = Mage::getModel('catalog/product')
        ->getCollection()
        ->addAttributeToSelect('*')
        ->addCategoryFilter($category)
        ->load();
        
        

        foreach($products as $product){
             $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $product->getId() );
                $qty = $stockItem->setData('qty');
                $inStock = $stockItem->getData('is_in_stock');

                
            $products_model = Mage::getModel('mobile/products');
            $product = Mage::getModel ( "catalog/product" )->load ( $product->getId() );
            $store_id = Mage::app()->getStore()->getId();
            $product_detail = array();
            $options = array();
            $price = array();
            $product_type = $product->getTypeId();
            if( $qty<1 || $inStock == 0){
                   $product_detail['stock_status'] = "out of stock";
                }else{
                    $product_detail['stock_status'] = "in of stock";
                }
            switch($product_type){
                case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE: {
                    $product_detail['attribute_options'] = $products_model->getProductOptions($product);
                    $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                    $price = number_format($price, 2, '.', '' );
                }break;
                case Mage_Catalog_Model_Product_Type::TYPE_SIMPLE: {
                    $product_detail['custom_options'] = $products_model->getProductCustomOptionsOption($product);
                    $product_detail['stock_level'] = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
                    $price = $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                    $price = number_format($price, 2, '.', '' );
                }break;
                case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE: {
                    $price = $products_model->collectBundleProductPrices($product);
                    $product_detail['bundle_option']  =  $products_model->getProductBundleOptions($product);
                }break;
                case Mage_Catalog_Model_Product_Type::TYPE_GROUPED: {
                    $product_detail['grouped_option']  =  $products_model->getProductGroupedOptions($product);
                }break;
                case Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL:  {
                    $price = $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                    $price = number_format($price, 2, '.', '' );
                }break;
                default: {
                    $price = $price = Mage::getModel('mobile/currency')->getCurrencyPrice(($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice()));
                    $price = number_format($price, 2, '.', '' );
                } break;
            }
            $product_detail['price'] = $price;
            $mediaGallery = array();
            foreach($product->getMediaGalleryImages()->getItems() as $image){
                $mediaGallery[] = $image['url'];
            }
            if(count($mediaGallery)<=0){
                array_push($mediaGallery,Mage::getModel('catalog/product_media_config')->getMediaUrl( $product->getImage()));
            };
            $product_detail['in_wishlist'] = false;
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customer_id =  Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId();
                $item_collection = Mage::getModel('wishlist/item')->getCollection()->addCustomerIdFilter($customer_id);
                foreach($item_collection as $item) {
                    if($item->getProductId()==$product->getId ()){
                        $product_detail['in_wishlist'] = true;
                    }
                }
            }
            $summaryData = Mage::getModel('review/review_summary')->setStoreId($store_id)  ->load($product->getId());
            $product_detail['entity_id'] = $product->getId ();
            $product_detail['cat_id'] = $product->getCategoryIds();
            $product_detail['category_id'] = $cat_id ;
            $product_detail['rating_summary'] = $summaryData->getRatingSummary();
            $product_detail['reviews_count'] = $summaryData->getReviewsCount();
            $product_detail['sku'] = $product->getSku ();
            $product_detail['status'] = $product->getStatus();
            $product_detail['name'] = $product->getName ();
            $product_detail['news_from_date'] = $product->getNewsFromDate ();
            $product_detail['news_to_date'] = $product->getNewsToDate ();
            $product_detail['product_type'] = $product->getTypeID();
            $product_detail['special_from_date'] = $product->getSpecialFromDate ();
            $product_detail['special_to_date'] = $product->getSpecialToDate ();
            $product_detail['image_url'] = $product->getImageUrl ();
            $product_detail['url_key'] = $product->getProductUrl ();
            $product_detail['regular_price_with_tax'] = number_format(Mage::helper('directory')->currencyConvert($product->getPrice(), $baseCurrency, $currentCurrency), 2, '.', '' );
            $product_detail['final_price_with_tax'] = number_format ( Mage::helper ( 'directory' )->currencyConvert ( $product->getSpecialPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' );
    //          'description' => nl2br ( $product->getDescription()),
            $product_detail['short_description'] = $product->getShortDescription();
    //        $product_detail['description'] = $product->getDescription();

            $product_detail['description'] = $product->getDescription();                   //add by wayne    /*long description*/
            $product_detail['additional'] = $products_model->getAdditionalFront($product); //add by wayne    /*additional information Visible on Product View Page on Front-end*/
            $product_detail['symbol'] = Mage::app ()->getLocale ()->currency ( Mage::app ()->getStore ()->getCurrentCurrencyCode () )->getSymbol ();
            $product_detail['options'] = $options;
            $product_detail['mediaGallery'] = $mediaGallery;
            $allProductByCategory[] = $product_detail;
        }
        echo json_encode(array('code'=>0, 'msg'=>null, 'model'=>$allProductByCategory));
    }
    public function getFilterableAttributesAction(){
      $this->init();
     
        $layer = Mage::getModel("catalog/layer");
    
        $attributes = $layer->getFilterableAttributes(); 
        $attr = array();
        foreach ($attributes as $attribute) {
            if ($attribute->getAttributeCode() == 'price') {
                $filterBlockName = 'catalog/layer_filter_price';
            } elseif ($attribute->getBackendType() == 'decimal') {
                $filterBlockName = 'catalog/layer_filter_decimal';
            } else {
                $filterBlockName = 'catalog/layer_filter_attribute';
            }
            $attr[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
     
        }
        echo json_encode(array('code'=>0, 'msg'=>null, 'model'=>$attr));
    }
    public function getFilterableProductAction(){
      $this->init();
    try{
        $catId = Mage::app()->getRequest()->getParam('categoryId'); //Pass categoryId in get variable
         $catId = 3;
        $storeId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        $page_no = Mage::app()->getRequest()->getParam('page_no');
        $params = Mage::app()->getRequest()->getParams(); //Pass attributes in key=>value form to filter results.
        $category = Mage::getModel('catalog/category')->load($catId);
     
        $layer = Mage::getModel("catalog/layer");
        $layer->setCurrentCategory($category);
        $attributes = $layer->getFilterableAttributes(); //get all filterable attributes available in selected category layered navigation
        $attr = array();
        foreach ($attributes as $attribute) {
            if ($attribute->getAttributeCode() == 'price') {
                $filterBlockName = 'catalog/layer_filter_price';
            } elseif ($attribute->getBackendType() == 'decimal') {
                $filterBlockName = 'catalog/layer_filter_decimal';
            } else {
                $filterBlockName = 'catalog/layer_filter_attribute';
            }
            $attr[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
     
        }
        $filters = array_intersect_key($params, $attr);
        $collection = $category->getProductCollection()
                        ->addAttributeToFilter(
                            'status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                        ->addAttributeToSelect('*');
                        print_r($attr);
                        //print_r($collection);

        foreach ($filters as $key => $value) {
            if($key == 'price'){
                $priceFilter = explode('-', $value);
                $collection->addAttributeToFilter('price', array('gteq' => $priceFilter[0]));
                $collection->addAttributeToFilter('price', array('lteq' => $priceFilter[1]));
            }
            else{
                $collection->addAttributeToFilter($key, array('in' => $value));
            }
        }
        $collection->setPage($page_no, 10);
       
    }
    catch (Exception $e) {
        $error = array('status' => false, 'message' => $e->getMessage());
    }
        echo json_encode(array('code'=>0, 'msg'=>$error, 'model'=>$collection));
    }


} 
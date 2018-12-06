<?php

/**
 * Class Lading_Api_SearchController
 */
class Lading_Api_SearchController extends Mage_Core_Controller_Front_Action {

    /**
     * get current user session
     * @return mixed
     */
	protected function _getSession(){
		return Mage::getSingleton ('catalog/session');
	}
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
    /**
     * search
     */
	public function indexAction() {
		$this->init();
		$page = ($this->getRequest ()->getParam ( 'page' )) ? ($this->getRequest ()->getParam ( 'page' )) : 1;
		$limit = ($this->getRequest ()->getParam ( 'limit' )) ? ($this->getRequest ()->getParam ( 'limit' )) : 5;
		$order = ($this->getRequest ()->getParam ( 'order' )) ? ($this->getRequest ()->getParam ( 'order' )) : 'relevance';
		$dir = ($this->getRequest ()->getParam ( 'dir' )) ? ($this->getRequest ()->getParam ( 'dir' )) : 'desc';
		$query = Mage::helper ( 'catalogsearch' )->getQuery();
		$query->setStoreId ( Mage::app ()->getStore ()->getId () );
		if ($query->getQueryText () != '') {
			if (Mage::helper ( 'catalogsearch' )->isMinQueryLength ()){
				$query->setId( 0 )->setIsActive( 1 )->setIsProcessed( 1 );
			}else{
				if ($query->getId ()) {
					$query->setPopularity ( $query->getPopularity () + 1 );
				} else {
					$query->setPopularity ( 1 );
				}
				if ($query->getRedirect ()) {
					$query->save ();
					$this->getResponse ()->setRedirect ( $query->getRedirect () );
					return;
				} else {
					$query->prepare ();
				}
			}
			Mage::helper('catalogsearch')->checkNotes();
			$collection = $query->getResultCollection();
			$collection->setCurPage($page)->setPageSize($limit)->addAttributeToFilter('visibility', array('in' => array(
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
			)))->addAttributeToSort($order, $dir);
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			$pages = $collection->setPageSize($limit)->getLastPageNumber();
			if($page <= $pages){
				$i = 1;
				$baseCurrency = Mage::app()->getStore()->getBaseCurrency()->getCode();
				$currentCurrency = Mage::app()->getStore()->getCurrentCurrencyCode();
				$store_id = Mage::app()->getStore()->getId();
				foreach($collection as $product){
					$product = Mage::getModel('catalog/product')->load($product->getId());
					$summaryData = Mage::getModel('review/review_summary')->setStoreId($store_id) ->load($product->getId());
					$price =($product->getSpecialPrice()) == null ? ($product->getPrice()) : ($product->getSpecialPrice());
					$regular_price_with_tax = $product->getPrice();
					$final_price_with_tax = $product->getSpecialPrice();
					/*$product_list [] = array(
						'entity_id' => $product->getId(),
						'sku' => $product->getSku(),
						'name' => $product->getName(),
						'rating_summary' => $summaryData->getRatingSummary(),
						'reviews_count' => $summaryData->getReviewsCount(),
						'news_from_date' => $product->getNewsFromDate (),
						'news_to_date' => $product->getNewsToDate(),
						'special_from_date' => $product->getSpecialFromDate(),
						'special_to_date' => $product->getSpecialToDate(),
						'image_url' => $product->getImageUrl(),
						'url_key' => $product->getProductUrl(),
						'price' =>  number_format(Mage::helper('directory')->currencyConvert($price, $baseCurrency, $currentCurrency), 2, '.', '' ),
						'regular_price_with_tax' => number_format(Mage::helper('directory')->currencyConvert($regular_price_with_tax, $baseCurrency, $currentCurrency), 2, '.', '' ),
						'final_price_with_tax' => number_format(Mage::helper('directory')->currencyConvert($final_price_with_tax, $baseCurrency, $currentCurrency), 2, '.', '' ),
						'symbol' => Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol(),
						'stock_level' => (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty()
					);*/
					
        			$products_model = Mage::getModel('mobile/products');
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
			        $product_list[] = $product_detail;
					$i ++;
				}
			}else{
				$product_list = array();
			}
			echo json_encode(
				array(
					'code'=>0,
					'msg'=>'search '.count($collection).' product success!',
					'model'=> array(
						'items'=> $product_list,
					)
				)
			);
			if(!Mage::helper('catalogsearch')->isMinQueryLength()){
				$query->save();
			}
		} else {
			echo json_encode(
				array(
					'code'=>0,
					'msg'=>null,
					'model'=>null,
					'error'=>'search keyword can not null!'
				)
			);
		}
	}



	/**
	 * get search number
	 */
	public function getSearchNumAction() {
		$this->init();
		$query = Mage::helper ( 'catalogsearch' )->getQuery();
		$query->setStoreId ( Mage::app ()->getStore ()->getId () );
		if ($query->getQueryText () != '') {
			if (Mage::helper ( 'catalogsearch' )->isMinQueryLength ()){
				$query->setId( 0 )->setIsActive( 1 )->setIsProcessed( 1 );
			}else{
				if ($query->getId ()) {
					$query->setPopularity ( $query->getPopularity () + 1 );
				} else {
					$query->setPopularity ( 1 );
				}
				if ($query->getRedirect ()) {
					$query->save ();
					$this->getResponse ()->setRedirect ( $query->getRedirect () );
					return;
				} else {
					$query->prepare ();
				}
			}
			Mage::helper('catalogsearch')->checkNotes();
			$collection = $query->getResultCollection();
			$collection->addAttributeToFilter('visibility', array('in' => array(
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
			)));
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			echo json_encode(
				array(
					'code'=>0,
					'msg'=>'search '.count($collection).' product success!',
					'model'=> count($collection)
				)
			);
			if(!Mage::helper('catalogsearch')->isMinQueryLength()){
				$query->save();
			}
		} else {
			echo json_encode(
				array(
					'code'=>0,
					'msg'=>null,
					'model'=>null,
					'error'=>'search keyword can not null!'
				)
			);
		}
	}


}


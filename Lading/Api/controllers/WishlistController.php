<?php
class Lading_Api_WishlistController extends Mage_Core_Controller_Front_Action {

	/**
     * add item to user wish list
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
	public function addAction() {
		$this->init();
		$return_result = array(
			'code' => 0,
			'msg' => null,
			'model' => null
		);
		if (! Mage::getStoreConfigFlag('wishlist/general/active')) {
			$return_result ['code'] = 1;
			$return_result ['msg'] = 'Wishlist Has Been Disabled By Admin';
			echo json_encode($return_result);
			return;
		}
		if (! Mage::getSingleton('customer/session')->isLoggedIn()) {
			$return_result ['code'] = 5;
			$return_result ['msg'] = 'Please Login First';
			echo json_encode($return_result);
			return;
		}
		$customer_id = Mage::getSingleton('customer/session')->getId();
		/*$customer = Mage::getModel('customer/customer');*/
		$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer_id, true);
		$product_id  = $this->getRequest()->getParam('product_id');
		$product  = Mage::getModel('catalog/product')->load($product_id);
		//$customer->load($customer_id);
		//$wishlist->loadByCustomer($customer_id);
		if($customer_id && $product_id){
			$res = $wishlist->addNewItem($product);
			$wishlist->save();
			
			if($res){
				$return_result['code'] = 0;
				$return_result['msg'] = "your product has been added in wishlist";
				$return_result['model'] = $res;
			}
			echo json_encode($return_result);
		}else{
			$return_result['code'] = 1;
			$return_result['msg'] = 'can not get customer info or product id';
			$return_result['model'] = null;
			echo json_encode($return_result);
		}
	}



    /**
     * get user wish list action
     */
	public function getWishlistAction(){
		$this->init();
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			echo json_encode(
				array(
					'code' => 0,
					'msg' => 'get user wish list success!',
					'model' => $this->_getWishlist()
				)
			);
		}else{
			echo json_encode(array(
				'code' => 5,
				'msg' => 'not user login',
				'model'=>array ()
			));
		}
	}




	/**
	 * delete wish list action
	 */
	public function delAction(){
		$this->init();
		$product_id  = $_GET['product_id'];
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customer_id =  Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId();
			$item_collection = Mage::getModel('wishlist/item')->getCollection()->addCustomerIdFilter($customer_id);
			foreach($item_collection as $item) {
				if($item->getProductId()==$product_id){
					$item->delete();
				}
			}
			echo json_encode(
				array(
					'code' => 0,
					'msg' => 'delete wish list product '.$product_id.' success!',
					'model' => $this->_getWishlist()
				)
			);
		}else{
			echo json_encode(array(
				'code' => 5,
				'msg' => 'not user login',
				'model'=>array ()
			));
		}
	}


    /**
     * get wish list method
     * @return array|bool
     */
	protected function _getWishlist() {
		$wishlist = Mage::registry ( 'wishlist' );
		$store_id = Mage::app()->getStore()->getId();
		$baseCurrency = Mage::app ()->getStore ()->getBaseCurrency ()->getCode ();
		$currentCurrency = Mage::app ()->getStore ()->getCurrentCurrencyCode ();
		if ($wishlist) {
			return $wishlist;
		}
		try {
			$wishlist = Mage::getModel ( 'wishlist/wishlist' )->loadByCustomer ( Mage::getSingleton ( 'customer/session' )->getCustomer (), true );
			Mage::register ( 'wishlist', $wishlist );
		} catch ( Mage_Core_Exception $e ) {
			Mage::getSingleton ( 'wishlist/session' )->addError ( $e->getMessage () );
		} catch ( Exception $e ) {
			Mage::getSingleton ( 'wishlist/session' )->addException ( $e, Mage::helper ( 'wishlist' )->__ ( 'Cannot create wishlist.' ) );
			return false;
		}
		$items = array ();
		foreach ( $wishlist->getItemCollection () as $item ) {
			$item = Mage::getModel ( 'catalog/product' )->setStoreId ( $item->getStoreId () )->load ( $item->getProductId () );
			$summaryData = Mage::getModel('review/review_summary')->setStoreId($store_id)  ->load($item->getId());
			 $products_model = Mage::getModel('mobile/products');
			 $option = null;
			if ($item->getId ()) {
				$product_type = $item->getTypeId();
	            switch($product_type){
	                case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE: {
	                    $option['attribute_options'] = $products_model->getProductOptions($item);
	                   
	                }break;
	                case Mage_Catalog_Model_Product_Type::TYPE_SIMPLE: {
	                    $option['custom_options'] = $products_model->getProductCustomOptionsOption($item);
	                }break;
	                case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE: {
	                   
	                    $option['bundle_option']  =  $products_model->getProductBundleOptions($item);
	                }break;
	                case Mage_Catalog_Model_Product_Type::TYPE_GROUPED: {
	                    $option['grouped_option']  =  $products_model->getProductGroupedOptions($item);
	                }break;
	                case Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL:  {
	                  
	                }break;
	                default: {
	                  
	                } break;
	            }
				$price = Mage::getModel('mobile/currency')->getCurrencyPrice(($item->getSpecialPrice()) == null ? ($item->getPrice()) : ($item->getSpecialPrice()));
				$items [] = array (
					'options' => $option,
					'name' => $item->getName (),
					'product_type' => $item->getTypeID(),
					'image_url' => $item->getImageUrl (),
					'url_key' => $item->getProductUrl (),
					'rating_summary' => $summaryData->getRatingSummary(),
					'reviews_count' => $summaryData->getReviewsCount(),
					'entity_id' => $item->getId (),
					'regular_price_with_tax' => number_format ( Mage::helper ( 'directory' )->currencyConvert ( $item->getPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' ),
					'final_price_with_tax' => number_format ( Mage::helper ( 'directory' )->currencyConvert ( $item->getSpecialPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' ),
					'price' => number_format($price, 2, '.', '' ),
					'sku' => $item->getSku(),
					'symbol' => Mage::app()->getLocale()->currency ( Mage::app ()->getStore ()->getCurrentCurrencyCode () )->getSymbol (),
					'stock_level' => (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($item)->getQty(),
					'short_description' => $item->getShortDescription()
				);
			}
		}
		return array (
			'wishlist' => $wishlist->getData (),
			'items' => $items
		);
	}
	/**
      * Add wishlist item to shopping cart
      */
     public function cartAction()
     {
     	$this->init();
     	$wishlist = Mage::registry ( 'wishlist' );
		$store_id = Mage::app()->getStore()->getId();
		$baseCurrency = Mage::app ()->getStore ()->getBaseCurrency ()->getCode ();
		$currentCurrency = Mage::app ()->getStore ()->getCurrentCurrencyCode ();
		if ($wishlist) {
			return $wishlist;
		}
		try {
			$wishlist = Mage::getModel ( 'wishlist/wishlist' )->loadByCustomer ( Mage::getSingleton ( 'customer/session' )->getCustomer (), true );
			Mage::register ( 'wishlist', $wishlist );
		} catch ( Mage_Core_Exception $e ) {
			Mage::getSingleton ( 'wishlist/session' )->addError ( $e->getMessage () );
		} catch ( Exception $e ) {
			Mage::getSingleton ( 'wishlist/session' )->addException ( $e, Mage::helper ( 'wishlist' )->__ ( 'Cannot create wishlist.' ) );
			return false;
		}
		$items = array ();
         $messages           = array();
         $urls               = array();
         $wishlistIds        = array();
         $notSalableNames    = array(); // Out of stock products message

         $id         = (int) $this->getRequest()->getParam('items');
         $item       = Mage::getModel('wishlist/item')->load($id);
 
         if($item->getWishlistId()==$wishlist->getId()) {
             try {
                 $product = Mage::getModel('catalog/product')->load($item->getProductId())->setQty(1);
                 $quote = Mage::getSingleton('checkout/cart')
                    ->addProduct($product)
                    ->save();
                 $item->delete();
             }
             catch(Exception $e) {
                 Mage::getSingleton('checkout/session')->addError($e->getMessage());
                 $url = Mage::getSingleton('checkout/session')->getRedirectUrl(true);
                 if ($url) {
                     $url = Mage::getModel('core/url')->getUrl('catalog/product/view', array(
                         'id'=>$item->getProductId(),
                         'wishlist_next'=>1
                     ));
                     Mage::getSingleton('checkout/session')->setSingleWishlistId($item->getId());
                     $this->getResponse()->setRedirect($url);
                 }
                 else {
                     $this->_redirect('*/*/');
                 }
                 return;
             }
         }
 
         if (Mage::getStoreConfig('checkout/cart/redirect_to_cart')) {
             $this->_redirect('checkout/cart');
         } else {
             if ($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL)) {
                 $this->getResponse()->setRedirect(
                     Mage::helper('core')->urlDecode($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL))
                 );
             } else {
                 $this->_redirect('*/*/');
             }
         }
     }

	  /**
      * Add all items to shoping cart
      *
      */
     public function allcartAction() {
     	$this->init();
     	$wishlist = Mage::registry ( 'wishlist' );
		$store_id = Mage::app()->getStore()->getId();
		$baseCurrency = Mage::app ()->getStore ()->getBaseCurrency ()->getCode ();
		$currentCurrency = Mage::app ()->getStore ()->getCurrentCurrencyCode ();
		if ($wishlist) {
			return $wishlist;
		}
		try {
			$wishlist = Mage::getModel ( 'wishlist/wishlist' )->loadByCustomer ( Mage::getSingleton ( 'customer/session' )->getCustomer (), true );
			Mage::register ( 'wishlist', $wishlist );
		} catch ( Mage_Core_Exception $e ) {
			Mage::getSingleton ( 'wishlist/session' )->addError ( $e->getMessage () );
		} catch ( Exception $e ) {
			Mage::getSingleton ( 'wishlist/session' )->addException ( $e, Mage::helper ( 'wishlist' )->__ ( 'Cannot create wishlist.' ) );
			return false;
		}
		$items = array ();
        $messages           = array();
        $urls               = array();
        $wishlistIds        = array();
        $notSalableNames    = array(); // Out of stock products message

         foreach ( $wishlist->getItemCollection () as $item ) {
             try {
                 $product = Mage::getModel('catalog/product')
                     ->load($item->getProductId())
                     ->setQty(1);
                 if ($product->isSalable()) {
                     Mage::getSingleton('checkout/cart')->addProduct($product);
                     $item->delete();
                 }
                 else {
                     $notSalableNames[] = $product->getName();
                 }
             } catch(Exception $e) {
                 $url = Mage::getSingleton('checkout/session')
                     ->getRedirectUrl(true);
                 if ($url) {
                     $url = Mage::getModel('core/url')
                         ->getUrl('catalog/product/view', array(
                             'id'            => $item->getProductId(),
                             'wishlist_next' => 1
                         ));
                     $urls[]         = $url;
                     $messages[]     = $e->getMessage();
                     $wishlistIds[]  = $item->getId();
                 } else {
                     $item->delete();
                 }
             }
             Mage::getSingleton('checkout/cart')->save();
         }
 		echo json_encode(
			array(
				'code' => 0,
				'msg' => 'success!!',
				'model' => $this->_getWishlist()
			)
		);
		return;


         /*if (count($notSalableNames) > 0) {
             Mage::getSingleton('checkout/session')
                 ->addNotice($this->__('This product(s) is currently out of stock:'));
             array_map(array(Mage::getSingleton('checkout/session'), 'addNotice'), $notSalableNames);
         }
 
         if ($urls) {
             Mage::getSingleton('checkout/session')->addError(array_shift($messages));
             $this->getResponse()->setRedirect(array_shift($urls));
 
             Mage::getSingleton('checkout/session')->setWishlistPendingUrls($urls);
             Mage::getSingleton('checkout/session')->setWishlistPendingMessages($messages);
             Mage::getSingleton('checkout/session')->setWishlistIds($wishlistIds);
         }
         else {
        	$this->_redirect('checkout/cart');
         }*/
     }
} 

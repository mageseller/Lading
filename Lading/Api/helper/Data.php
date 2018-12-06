<?php

class Lading_Api_Helper_Data extends Mage_Core_Helper_Abstract
{

 	public function formatPriceForXml($price)
    {
        return sprintf('%01.2F', $price);
    }


    public function getloginCustomerById($custId){
    	echo "dfsd";
    	die;
    	return $this;
    	/*$this->authenticateCustomer($custId);
    	$session = Mage::getSingleton('customer/session');
		$session->loginById($custId);*/
    }
   /* public function authenticateCustomer($custId){
    	$customerId = $custId;
    	$session = Mage::getSingleton('customer/session');
    	try{
	    	if ($session->isLoggedIn()) {
	    		if($session->getCustomer()->getId() != $customerId ){
	    			$session->logout();

	    		}
	    		$session->loginById($custId);
	    	}
    	}
	    catch (Exception $e){
			$key ="authenticate=N";
		}
    	
    }*/
}

?>
<?php
/**
 * Created by PhpStorm.
 * User: leo
 * Date: 5/14/15
 * Time: 2:27 PM
 */


/**
 * Class Lading_Api_CustomerController
 */
class Lading_Api_AddressController extends Mage_Core_Controller_Front_Action
{

    /**
     * 获取用户地址列表
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
    public function getAddressListAction(){
        $this->init();
        $result = array (
            'code' => 0,
            'msg' => null,
            'model' => null
        );
        $session = Mage::getSingleton('customer/session');
        if (!$session->isLoggedIn()) {
            $result['code'] = 5;
            $result['msg'] = 'user is not login';
            echo json_encode($result);
            return;
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $addressList = Mage::getModel('mobile/address')->getCustomerAddressList($customer);
        echo json_encode(
            array(
                'code' => 0,
                'msg' => 'get user address list success!',
                'model' => $addressList
            )
        );
    }



    /**
     * 获取用户地址
     */
    public function getAddressAction(){
        $this->init();
        $result = array (
            'code' => 0,
            'msg' => null,
            'model' => null
        );
        $session = Mage::getSingleton('customer/session');
        if (!$session->isLoggedIn()) {
            $result['code'] = 5;
            $result['msg'] = 'user is not login';
            echo json_encode($result);
            return;
        }
        $addressId = $this->getRequest()->getParam( 'address_id' );
        $return_address = Mage::getModel('mobile/address')->getAddressById($addressId);
        echo json_encode(
            array(
                'code' => 0,
                'msg' => 'get user address success!',
                'model' => $return_address
            )
        );
    }
    public function getAddressByCustomerAction(){
        $this->init();
        $result = array (
            'code' => 0,
            'msg' => null,
            'model' => null
        );
        $session = Mage::getSingleton('customer/session');
        if (!$session->isLoggedIn()) {
            $result['code'] = 5;
            $result['msg'] = 'user is not login';
            echo json_encode($result);
            return;
        }
        $addressType = $this->getRequest()->getParam('address_type');
        if($addressType == "billing"){
            $addressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
        }else if($addressType == "shipping"){
            $addressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
        }
      
        if ($addressId){
            $address = Mage::getModel('customer/address')->load($addressId)->getData();
            $return_address =  $address;
            echo json_encode(
                array(
                    'code' => 0,
                    'msg' => 'get user address success!',
                    'model' => $return_address
                )
            );
        }else{
           echo json_encode(
                array(
                    'code' => 0,
                    'msg' => 'Sorry please set '.$addressType.' address',
                    'model' => array()
                )
            ); 

        }
        

     
        
    }



    /**
     * Delete address
     * @return boolean
     */
    public function deleteAction(){
        $this->init();
        $addressId = $this->getRequest()->getParam ( 'address_id' );
        $result = array (
            'code' => 0,
            'msg' => "Address delete Successfully",
            'model' => true
        );
        $address = Mage::getModel('customer/address')
            ->load($addressId);
        if (!$address->getId()) {
            $result['msg'] = 'not_exists';
            $result['model'] = false;
        }
        try {
            $address->delete();
        } catch (Mage_Core_Exception $e) {
            $result['msg'] = $e->getMessage();
            $result['model'] = false;
        }
        echo json_encode($result);
    }

    /**
     * Create new address for customer
     * @return mixed
     */
    public function createAction(){
        $this->init();
        $session = Mage::getSingleton('customer/session');
        $result = array (
            'code' => 0,
            'msg' => null,
            'model' => null
        );
        if (!$session->isLoggedIn()) {
            $result['code'] = 5;
            $result['msg'] = 'user is not login';
            echo json_encode($result);
            return;
        }
        $postData = $this->getRequest()->getPost();
        $addressData = array();
        $addressData['address_book_id'] = $postData['address_book_id'];
        $addressData['address_type'] = $postData['address_type'];
        $addressData['lastname'] = $postData['lastname'];
        $addressData['firstname'] = $postData['firstname'];
        $addressData['suffix'] = $postData['suffix'];
        $addressData['telephone'] = $postData['telephone'];
        $addressData['company'] = $postData['company'];
        $addressData['fax'] = $postData['fax'];
        $addressData['postcode'] = $postData['postcode'];
        $addressData['city'] = $postData['city'];
        $addressData['address1'] = $postData['address1'];
        $addressData['address2'] = $postData['address2'];
        $addressData['country_name'] = $postData['country_name'];
        $addressData['country_id'] = $postData['country_id'];
        $addressData['state'] = $postData['state'];
        $addressData['zone_name'] = $postData['zone_name'];
        $addressData['zone_id'] = $postData['zone_id'];
        if (!is_null($addressData)) {
            $customer = $session->getCustomer();
            $address = Mage::getModel('customer/address');
            $addressId = $addressData['address_book_id'];
            if ($addressId) {
                $existsAddress = $customer->getAddressById($addressId);
                if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                    $address->setId($existsAddress->getId());
                }
            }
            $errors = array();
            try {
             
                $addressType = explode(',', $addressData['address_type']);
              
                $address->setCustomerId($customer->getId())
                    ->setIsDefaultBilling(strtolower($addressType[0]) == 'billing' || strtolower($addressType[1]) == 'billing')
                    ->setIsDefaultShipping(strtolower($addressType[0]) == 'shipping' || strtolower($addressType[1]) == 'shipping');
                $address->setLastname($addressData['lastname']);
                $address->setFirstname($addressData['firstname']);
                $address->setSuffix($addressData['suffix']);
                $address->setTelephone($addressData['telephone']);
                $address->setCompany($addressData['company']);
                $address->setFax($addressData['fax']);
                $address->setPostcode($addressData['postcode']);
                $address->setCity($addressData['city']);
                $address->setStreet(array($addressData['address1'], $addressData['address2']));
                $address->setCountry($addressData['country_name']);
                $address->setCountryId($addressData['country_id']);
                if (isset($addressData['state'])) {
                    $address->setRegion($addressData['state']);
                    $address->setRegionId(null);
                } else {
                    $address->setRegion($addressData['zone_name']);
                    $address->setRegionId($addressData['zone_id']);
                }
                $addressErrors = $address->validate();
                if ($addressErrors !== true) {
                    $errors = array_merge($errors, $addressErrors);
                }
                $addressValidation = count($errors) == 0;
                if (true === $addressValidation) {
                    $address->save();
                    $result['code'] = 0;
                    $result['msg'] = 'save or update user address success!';
                    echo json_encode($result);
                    return;
                } else {
                    if (is_array($errors)) {
                        $result['code'] = 3;
                        $result['msg'] = $errors;
                    } else {
                        $result['code'] = 3;
                        $result['msg'] = 'Can\'t save or update address';
                    }
                    echo json_encode($result);
                    return;
                }
            } catch (Mage_Core_Exception $e) {
                $result['code'] = 4;
                $result['msg'] = $e->getMessage();
                echo json_encode($result);
                return;
            } catch (Exception $e) {
                $result['code'] = 5;
                $result['msg'] = $e->getMessage();
                echo json_encode($result);
                return;
            }
        } else {
            $result['code'] = 6;
            $result['msg'] = 'address data is null!';
            echo json_encode($result);
            return;
        }
    }
    public function updateAction(){
        $this->init();
        
        $postData  = $this->getRequest()->getPost();
        $addressId   = $postData['address_id'];
        unset($postData['address_id']);
        unset($postData['address_type']);
        if($postData['address1'] || $postData['address2']){
            $postData['street'] = array($postData['address1'], $postData['address2']);
            unset($postData['address1']);
            unset($postData['address2']);
        }
        
        $addressData = $postData;
        //print_r($addressData);
        $address     = Mage::getModel('customer/address')->load($addressId);
        $address->setCustomerId($address->getCustomer()->getId());
        foreach ($addressData as $addressCode => $addressValue) {
            if (isset($addressData[$addressCode])) {
                $address->setData($addressCode,$addressValue);
            }
        }
        try {
            $address->setId($addressId);
            $address->save();
        }
        catch (Mage_Core_Exception $e) {
            echo $e->getMessage();
        }
    }

}


<?php
/*
   Copyright 2015 idealo internet GmbH

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/


class fcidealo_import_orders extends fcidealo_base
{
    
    protected $blIsDebugMode = false;
    protected $aTestProductMap = array();
    protected $sLastOrderHandleErrorType = '';
    protected $_dOrderVat = null;
    
    protected function orderAlreadyExists($aOrder) 
    {
        $sQuery = "SELECT oxid FROM oxorder WHERE FCIDEALO_ORDERNR = ".oxDb::getDb()->quote($aOrder['order_number'])." AND oxshopid = '".self::$_sShopId."' LIMIT 1";
        $sOxid = oxDb::getDb()->GetOne($sQuery);
        if($sOxid) {
            return true;
        }
        return false;
    }
    
    /**
     * Checks if essential data harmonizes with shop data
     * 
     * @param array $aOrder
     * @return bool
     */
    protected function orderDataIsValid( $aOrder ) {
        $blOrderIsValid                     = true;
        $this->sLastOrderHandleErrorType    = '';
        
        if ( $this->orderAlreadyExists( $aOrder ) ) {
            $blOrderIsValid = false;
            if ( !$blOrderIsValid ) {
                $this->sLastOrderHandleErrorType = 'Order already exists';
            }
        }
        
        if ( $blOrderIsValid ) {
            // check if payment isset and active
            $sPaymentType = $this->getPaymentType( $aOrder );
            $blOrderIsValid = $this->checkPaymentAvailable( $sPaymentType );
            if ( !$blOrderIsValid ) {
                $this->sLastOrderHandleErrorType = 'Payment not available in shop';
            }
        }
        
        if ( $blOrderIsValid ) {
            // check if articles are available in shop
            $aArticles = $aOrder['line_items'];
            $blOrderIsValid = $this->orderArticlesExisting( $aArticles );
            if ( !$blOrderIsValid ) {
                $this->sLastOrderHandleErrorType = 'Not all articles are available in shop';
            }
        }

        return $blOrderIsValid;
    }
    
    /**
     * Checks if payment is available in shop
     * 
     * @param string $sPaymentType
     * @return bool
     */
    protected function checkPaymentAvailable( $sPaymentType ) {
        $oDb                = oxDb::getDb( oxDb::FETCH_MODE_ASSOC );
        $blPaymentValid     = true;
        
        if ( $sPaymentType ) {
            $sQuery = "SELECT OXID FROM oxpayments WHERE OXID = ".$oDb->quote( $sPaymentType )." LIMIT 1";
            $mResult = $oDb->GetOne( $sQuery );
            if ( !$mResult ) {
                $blPaymentValid = false;
            }
        }
        else {
            $blPaymentValid = false;
        }
        
        return $blPaymentValid;
    }
    
    /**
     * Checks if all articles of order are available in shop
     * 
     * @param array $aArticles
     * @return bool
     */
    protected function orderArticlesExisting( $aArticles ) {
        $blArticlesValid = true;

        if ( is_array( $aArticles ) && count( $aArticles ) > 0 ) {
            foreach ( $aArticles as $aItem ) {
                $sProductId = $this->getProductId($aItem);
                if ( !$sProductId ) {
                    $blArticlesValid = false;
                }
            }
        } else {
            $blArticlesValid = false;
        }

        return $blArticlesValid;
    }

    protected function getSplittedStreetData($sStreetAddr)
    {
        $iLastSpace = mb_strrpos($sStreetAddr, ' ');
        if ($iLastSpace !== false) {
            $sStreetNr = mb_substr($sStreetAddr, $iLastSpace, mb_strlen($sStreetAddr));
            $sStreet = mb_substr($sStreetAddr, 0, $iLastSpace);
        } else {
            $sStreetNr = '';
            $sStreet = $sStreetAddr;
        }
        
        return array($sStreet, $sStreetNr);
    }

    protected function getUserSal($sFirstname) 
    {
        if($this->getCacheValue($sFirstname, 'sal') === null) {
            $sQuery = "SELECT oxsal FROM oxuser WHERE oxfname = ".oxDb::getDb()->quote($sFirstname)." AND oxsal IN ('MR', 'MRS') LIMIT 1";
            $sSal = oxDb::getDb()->getOne($sQuery);
            if(!$sSal) {
                $sSal = '';
            }
            $this->setCacheValue($sFirstname, $sSal, 'sal');
        }
        return $this->getCacheValue($sFirstname, 'sal');
    }
    
    protected function getCountryId($sCountryCode)
    {
        if($this->getCacheValue($sCountryCode, 'country') === null) {
            $sQuery = "SELECT oxid FROM oxcountry WHERE OXISOALPHA2 = '{$sCountryCode}' LIMIT 1";
            $sOxid = oxDb::getDb()->getOne($sQuery);
            if (!$sOxid) {
                $sOxid = '-1';   
            }   
            $this->setCacheValue($sCountryCode, $sOxid, 'country');
        }
        
        return $this->getCacheValue($sCountryCode, 'country');       
    }
    
    protected function getPaymentId($aOrder, $aOxidOrder)
    {
        $aData = array();
        $aData['OXID'] = oxUtilsObject::getInstance()->generateUID();
        $aData['OXUSERID'] = $aOxidOrder['OXUSERID'];
        $aData['OXPAYMENTSID'] = $aOxidOrder['OXPAYMENTTYPE'];
        $aData['OXVALUE'] = '';
        $this->insertRecord($aData, 'oxuserpayments');
        
        return $aData['OXID'];
    }
    
    protected function getPaymentType($aOrder)
    {
        $sPaymentType = $aOrder['payment']['payment_method'];
        
        $sPaymentMap = self::_getShopConfVar('sIdealoPaymentMap');
        $aPaymentMap = unserialize(html_entity_decode($sPaymentMap));
        if($aPaymentMap && isset($aPaymentMap[$sPaymentType])) {
            $sPaymentType = $aPaymentMap[$sPaymentType];
        }
        return $sPaymentType;        
    }
    
    protected function getOrderFolder($aOrder)
    {
        return 'ORDERFOLDER_NEW';        
    }
    
    protected function setDeliveryInfo($aOxidOrder, $aOrder)
    {
        $sDeliveryType = $aOrder['fulfillment']['type'];
        $aOxidOrder['OXDELTYPE'] = $sDeliveryType;
        
        $sDeliveryMap = self::_getShopConfVar('sIdealoDeliveryMap');
        $aDeliveryMap = unserialize(html_entity_decode($sDeliveryMap));
        if($aDeliveryMap && isset($aDeliveryMap[$sDeliveryType])) {
            $aOxidOrder['OXDELTYPE'] = $aDeliveryMap[$sDeliveryType]['type'];
            if($aDeliveryMap[$sDeliveryType]['carrier'] != '' && $aDeliveryMap[$sDeliveryType]['type'] != 'other') {
                $aOxidOrder['FCIDEALO_DELIVERY_CARRIER'] = $aDeliveryMap[$sDeliveryType]['carrier'];
            }
        }
        return $aOxidOrder;
    }
    
    protected function getNextOrderNr() 
    {
        $blSeperate = $this->getConfig()->getConfigParam('blSeparateNumbering');
        $sIdent = $blSeperate ? 'oxOrder_' . self::$_sShopId : 'oxOrder';
        return oxNew('oxCounter')->getNext($sIdent);
    }
    
    protected function getUserId($sEmail)
    {
        $sQuery = "SELECT oxid FROM oxuser WHERE oxusername = '{$sEmail}'";
        $sOxid = oxDb::getDb()->getOne($sQuery);
        
        if ($sOxid) {
            return $sOxid;
        }
        return false;
    }
    
    protected function handleUser($aOrder)
    {
        $oUser = oxNew('oxuser');
        $sUserId = $this->getUserId($aOrder['customer']['email']);
        if($sUserId) {
            $oUser->load($sUserId);
        }
        $aStreetAddr = $this->getSplittedStreetData($aOrder['billing_address']['address1']); 
        $aData = array();
        $aData['oxrights']      = 'user';
        $aData['oxusername']    = $aOrder['customer']['email'];
        $aData['oxfname']       = $aOrder['billing_address']['given_name'];
        $aData['oxlname']       = $aOrder['billing_address']['family_name'];
        $aData['oxzip']         = $aOrder['billing_address']['zip'];
        $aData['oxcity']        = $aOrder['billing_address']['city'];
        $aData['oxcountryid']   = $this->getCountryId($aOrder['billing_address']['country']);
        $aData['oxfon']         = $aOrder['customer']['phone'];
        $aData['oxstreet']      = $aStreetAddr[0];
        $aData['oxstreetnr']    = $aStreetAddr[1];
        $aData['oxaddinfo']     = $aOrder['billing_address']['address2'];
        
        $oUser->assign($aData);
        $oUser->save();
        
        return $oUser->getId();
    }
    
    protected function getProductId($aItem) 
    {
        $sSKU = $aItem['sku'];
        if($this->getCacheValue($sSKU, 'productId') === null) {
            $sQuery = "SELECT oxid FROM oxarticles WHERE oxartnum = ".oxDb::getDb()->quote($sSKU)." LIMIT 1";
            $sProdId = oxDb::getDb()->getOne($sQuery);
            if(!$sProdId) {
                if($this->blIsDebugMode && isset($this->aTestProductMap[$sSKU])) {
                    $sProdId = $this->aTestProductMap[$sSKU];
                } else {
                    $this->sendProductNotFoundMail($aItem);
                }
            }
            $this->setCacheValue($sSKU, $sProdId, 'productId');
        }
        return $this->getCacheValue($sSKU, 'productId');
    }

    protected function getOrderVat($aOrder)
    {
        if ($this->_dOrderVat === null) {
            $dVat = false;
            foreach ($aOrder['line_items'] as $aItem) {
                $sProductId = $this->getProductId($aItem);
                $oProduct = oxNew('oxarticle');
                if ($oProduct->load($sProductId)) {
                    $dVat = $oProduct->getPrice()->getVat();
                    break;
                }
            }
            if (!$dVat && array_key_exists('vat_rate', $aOrder) !== false) {
                $dVat = $aOrder['vat_rate'];
            }
            $this->_dOrderVat = $dVat;
        }
        return $this->_dOrderVat;
    }
    
    protected function updateStock($aData)
    {
        $oOrderArticle = oxNew('oxorderarticle');
        $oOrderArticle->load($aData['OXID']);
        $oOrderArticle->updateArticleStock($aData['OXAMOUNT'] * (-1), $this->getConfig()->getConfigParam('blAllowNegativeStock'));
    }
    
    protected function getAddressHash($aAddress)
    {
        $sAddressString  = '';
        $sAddressString .= $aAddress['address1'];
        $sAddressString .= $aAddress['address2'];
        $sAddressString .= $aAddress['city'];
        $sAddressString .= $aAddress['country'];
        $sAddressString .= $aAddress['given_name'];
        $sAddressString .= $aAddress['family_name'];
        $sAddressString .= $aAddress['zip'];
        return md5($sAddressString);
    }
    
    protected function getFulfillmentOptionTitle($aFulfillmentItem)
    {
        return oxRegistry::getLang()->translateString('FCIDEALO_'.$aFulfillmentItem['name'], null, true);
        
    }
    
    protected function hasDifferentDeliveryAddress($aOrder)
    {
        $sBillingHash = $this->getAddressHash($aOrder['billing_address']);
        $sDeliveryHash = $this->getAddressHash($aOrder['shipping_address']);
        if($sBillingHash != $sDeliveryHash) {
            return true;
        }
        return false;
    }
    
    protected function formatPrice($dPrice)
    {
        return number_format((double)$dPrice, 2, ".", "");
    }
    
    protected function getNetPrice($dBrutPrice, $dVat)
    {
        $dNetPrice = $dBrutPrice / (1 + ($dVat / 100));
        return $dNetPrice;
    }

    protected function getFulfillmentOptionSum($aOrder)
    {
        $dSum = 0;
        if(isset($aOrder['fulfillment']['fulfillment_options']) && count($aOrder['fulfillment']['fulfillment_options']) > 0) {
            foreach ($aOrder['fulfillment']['fulfillment_options'] as $aFulfillmentItem) {
                $dSum += $aFulfillmentItem['price'];
            }
        }
        return $dSum;
    }
    
    protected function handleOrderarticles($aIdealoOrder, $sOrderId)
    {
        $dVatRate = $this->getOrderVat($aIdealoOrder);
        foreach ($aIdealoOrder['line_items'] as $aItem) {
            
            $dNetPrice = $this->getNetPrice($aItem['item_price'], $dVatRate);
            
            $aData = array();
            $aData['OXID']          = oxUtilsObject::getInstance()->generateUID();
            $aData['OXORDERID']     = $sOrderId;
            $aData['OXAMOUNT']      = $aItem['quantity'];
            $aData['OXARTID']       = $this->getProductId($aItem);
            $aData['OXARTNUM']      = $aItem['sku'];
            $aData['OXTITLE']       = $aItem['title'];
            $aData['OXNPRICE']      = $this->formatPrice($dNetPrice);
            $aData['OXPRICE']       = $this->formatPrice($aItem['item_price']);
            $aData['OXBPRICE']      = $this->formatPrice($aItem['item_price']);
            $aData['OXNETPRICE']    = $this->formatPrice(($dNetPrice * $aItem['quantity']));
            $aData['OXBRUTPRICE']   = $this->formatPrice(($aItem['price']));
            $aData['OXVATPRICE']    = $aData['OXBRUTPRICE'] - $aData['OXNETPRICE'];
            $aData['OXVAT']         = $this->formatPrice($dVatRate);
            $aData['OXINSERT']      = date('Y-m-d');
            $aData['oxsubclass']    = 'oxarticle';
            $aData['oxordershopid'] = self::$_sShopId;

            $aData = $this->modifyOrderarticle($aIdealoOrder, $aData);
            
            if($this->insertRecord($aData, 'oxorderarticles') && strlen($aData['OXARTID']) > 0) {
                $this->updateStock($aData);
            }
        }

        if(isset($aIdealoOrder['fulfillment']['fulfillment_options']) && count($aIdealoOrder['fulfillment']['fulfillment_options']) > 0) {
            foreach ($aIdealoOrder['fulfillment']['fulfillment_options'] as $aFulfillmentItem) {
                $dNetPrice = $this->getNetPrice($aFulfillmentItem['price'], $dVatRate);

                $aData = array();
                $aData['OXID']          = oxUtilsObject::getInstance()->generateUID();
                $aData['OXORDERID']     = $sOrderId;
                $aData['OXAMOUNT']      = 1;
                $aData['OXARTID']       = '';
                $aData['OXARTNUM']      = '';
                $aData['OXTITLE']       = $this->getFulfillmentOptionTitle($aFulfillmentItem);
                $aData['OXNPRICE']      = $this->formatPrice($dNetPrice);
                $aData['OXPRICE']       = $this->formatPrice($aFulfillmentItem['price']);
                $aData['OXBPRICE']      = $this->formatPrice($aFulfillmentItem['price']);
                $aData['OXNETPRICE']    = $this->formatPrice($dNetPrice);
                $aData['OXBRUTPRICE']   = $this->formatPrice($aFulfillmentItem['price']);
                $aData['OXVATPRICE']    = $this->formatPrice(($aFulfillmentItem['price'] - $dNetPrice));
                $aData['OXVAT']         = $this->formatPrice($dVatRate);
                $aData['OXINSERT']      = date('Y-m-d');
                $aData['oxsubclass']    = 'oxarticle';
                $aData['oxordershopid'] = self::$_sShopId;

                $aData = $this->modifyOrderarticle($aIdealoOrder, $aData);

                $this->insertRecord($aData, 'oxorderarticles');
            }
        }
    }
    
    protected function handleOrder($aOrder)
    {
        if( $this->orderDataIsValid( $aOrder ) === true ) {
            $sUserId = $this->handleUser($aOrder);
            $dVatRate = $this->getOrderVat($aOrder);
            
            $aOxidOrder = array();
            $aOxidOrder['OXID']                 = oxUtilsObject::getInstance()->generateUID();
            $aOxidOrder['OXSHOPID']             = self::$_sShopId;
            $aOxidOrder['OXUSERID']             = $sUserId;
            $aOxidOrder['OXORDERDATE']          = date('Y-m-d H:i:s', strtotime($aOrder['created_at']));
            $aOxidOrder['OXORDERNR']            = $this->getNextOrderNr();
            $aOxidOrder['FCIDEALO_ORDERNR']     = $aOrder['order_number'];
            
            // **** billing data ****
            $aStreetAddr = $this->getSplittedStreetData($aOrder['billing_address']['address1']);
            $aOxidOrder['OXBILLCOMPANY']        = '';
            $aOxidOrder['OXBILLEMAIL']          = $aOrder['customer']['email'];
            $aOxidOrder['OXBILLFNAME']          = $aOrder['billing_address']['given_name'];
            $aOxidOrder['OXBILLLNAME']          = $aOrder['billing_address']['family_name'];
            $aOxidOrder['OXBILLSTREET']         = $aStreetAddr[0];
            $aOxidOrder['OXBILLSTREETNR']       = $aStreetAddr[1];
            $aOxidOrder['OXBILLADDINFO']        = $aOrder['billing_address']['address2'];
            $aOxidOrder['OXBILLUSTID']          = '';
            $aOxidOrder['OXBILLCITY']           = $aOrder['billing_address']['city'];
            $aOxidOrder['OXBILLCOUNTRYID']      = $this->getCountryId($aOrder['billing_address']['country']);
            $aOxidOrder['OXBILLZIP']            = $aOrder['billing_address']['zip'];
            $aOxidOrder['OXBILLFON']            = $aOrder['customer']['phone'];
            $aOxidOrder['OXBILLFAX']            = '';
            $aOxidOrder['OXBILLSAL']            = $this->getUserSal($aOrder['billing_address']['given_name']);
            
            if($this->hasDifferentDeliveryAddress($aOrder)) {
                // **** shipping data ****
                $aStreetAddr = $this->getSplittedStreetData($aOrder['shipping_address']['address1']);
                $aOxidOrder['OXDELCOMPANY']     = '';
                $aOxidOrder['OXDELFNAME']       = $aOrder['shipping_address']['given_name'];
                $aOxidOrder['OXDELLNAME']       = $aOrder['shipping_address']['family_name'];
                $aOxidOrder['OXDELSTREET']      = $aStreetAddr[0];
                $aOxidOrder['OXDELSTREETNR']    = $aStreetAddr[1];
                $aOxidOrder['OXDELADDINFO']     = $aOrder['shipping_address']['address2'];
                $aOxidOrder['OXDELCITY']        = $aOrder['shipping_address']['city'];
                $aOxidOrder['OXDELCOUNTRYID']   = $this->getCountryId($aOrder['shipping_address']['country']);
                $aOxidOrder['OXDELZIP']         = $aOrder['shipping_address']['zip'];
                $aOxidOrder['OXDELFON']         = '';
                $aOxidOrder['OXDELFAX']         = '';
                $aOxidOrder['OXDELSAL']         = $this->getUserSal($aOrder['shipping_address']['given_name']);
            }
            
            // **** other data ****
            $dFulfillmentOptionSum              = $this->getFulfillmentOptionSum($aOrder);
            $dShippingCost                      = $aOrder['total_shipping'] - $dFulfillmentOptionSum;
            $dBrutSum                           = $aOrder['total_line_items_price'] + $dFulfillmentOptionSum;
            $dNetSum                            = $this->getNetPrice($dBrutSum, $dVatRate);
            $dTotalTax                          = $dBrutSum - $dNetSum;
            
            $aOxidOrder['OXTOTALBRUTSUM']       = $this->formatPrice($dBrutSum);
            $aOxidOrder['OXTOTALNETSUM']        = $this->formatPrice($dNetSum);
            $aOxidOrder['OXTOTALORDERSUM']      = $this->formatPrice($aOrder['total_price']);
            $aOxidOrder['OXARTVAT1']            = $this->formatPrice($dVatRate);
            $aOxidOrder['OXARTVATPRICE1']       = $this->formatPrice($dTotalTax);
            $aOxidOrder['OXARTVAT2']            = 0;
            $aOxidOrder['OXARTVATPRICE2']       = 0;
            $aOxidOrder['OXDELVAT']             = $this->formatPrice($dVatRate);
            $aOxidOrder['OXDELCOST']            = $this->formatPrice($dShippingCost);
            $aOxidOrder['OXPAYVAT']             = 0;
            $aOxidOrder['OXPAYCOST']            = 0;// not existing in Idealo?
            $aOxidOrder['OXVOUCHERDISCOUNT']    = 0;// not existing in Idealo?
            $aOxidOrder['OXCURRENCY']           = $aOrder['currency'];
            $aOxidOrder['OXCURRATE']            = '1'; // ???
            $aOxidOrder['OXSTORNO']             = '0';
            $aOxidOrder['OXTRANSSTATUS']        = 'OK';
            $aOxidOrder['OXLANG']               = 0;
            $aOxidOrder['OXTRANSID']            = $aOrder['payment']['transaction_id'];
            $aOxidOrder['OXPAYMENTTYPE']        = $this->getPaymentType($aOrder);
            $aOxidOrder['OXPAYMENTID']          = $this->getPaymentId($aOrder, $aOxidOrder);
            $aOxidOrder['OXPAID']               = date('Y-m-d H:i:s');
            $aOxidOrder['OXFOLDER']             = $this->getOrderFolder($aOrder);
            $aOxidOrder                         = $this->setDeliveryInfo($aOxidOrder, $aOrder);
            
            $aOxidOrder = $this->modifyOrder($aOrder, $aOxidOrder);
            
            $this->insertRecord($aOxidOrder, 'oxorder');
            $this->handleOrderarticles($aOrder, $aOxidOrder['OXID']);
            $this->sendShopOrderNr($aOrder['order_number'], $aOxidOrder['OXORDERNR'], $aOxidOrder['OXID']);
        }
        else {
            $this->sendHandleOrderError( $aOrder, $this->sLastOrderHandleErrorType );
            $this->sendOrderRevocation( $aOrder['order_number'], 'MERCHANT_DECLINE', $this->sLastOrderHandleErrorType );
        }
    }
    
    /*
     * Hook for custom function - modifying existing fields or defining additional fields for input and update statements for the order
     */
    protected function modifyOrder($aIdealoOrder, $aOxidOrder)
    {
        return $aOxidOrder;
    }
    
    /*
     * Hook for custom function - modifying existing fields or defining additional fields for input and update statements for the orderarticle
     */
    protected function modifyOrderarticle($aIdealoOrder, $aOxidOrderarticle)
    {
        return $aOxidOrderarticle;
    }
    
    protected function getOrders()
    {
        $aOrders = array();
        
        try {
            $oClient = fcidealo_base::getClient();
            $aOrders = $oClient->getOrders();
            if($aOrders === false) {
                $this->sendGetOrdersErrorMail( $oClient );
            }
        } 
        catch ( Exception $oEx ) {
            $this->sendExceptionMail( $oEx. 'script: fcidealo_import_orders::getOrders()' );
        }
        
        return $aOrders;
    }
    
    protected function importOrders()
    {
        $aOrders = $this->getOrders();
        
        if ( is_array( $aOrders ) && count( $aOrders ) > 0 ) {
            foreach ($aOrders as $aOrder) {
                $this->handleOrder($aOrder);
            }
        }
        else {
            /**
             * @todo Maybe we should introduce some debug levels where customer can choose if he wants to be notified if there are no orders
             */
        }
    }
    
    public function start()
    {
        $this->importOrders();
    }

}
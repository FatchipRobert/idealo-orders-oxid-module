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


class fcidealo_module_config extends fcidealo_module_config_parent
{

    protected $_aDefaultIdealoPaymentTypes = array(
        'CREDITCARD' => 'Credit Card Payment Method (Heidelpay)',
        'SOFORT' => 'SOFORT Überweisung Payment Method',
        'PAYPAL' => 'PayPal Payment Method',
    );
    
    protected $_aDefaultIdealoDeliveryTypes = array(
        'POSTAL' => 'Post-Versand',
        'FORWARDING' => 'Spedition',
    );
    
    protected $_aDefaultDeliveryCarriers = array(
        'Cargo' => 'Cargo',
        'DHL' => 'DHL',
        'DPD' => 'DPD',
        'Der Courier' => 'Der Courier',
        'Deutsche Post' => 'Deutsche Post',
        'FedEx' => 'FedEx',
        'GLS' => 'GLS',
        'GO!' => 'GO!',
        'GdSK' => 'GdSK',
        'Hermes' => 'Hermes',
        'Midway' => 'Midway',
        'Noxxs Logistic' => 'Noxxs Logistic',
        'TOMBA' => 'TOMBA',
        'UPS' => 'UPS',
        'eparcel' => 'eparcel',
        'iloxx' => 'iloxx',
        'paket.ag' => 'paket.ag',
        'primeMail' => 'primeMail',
        'other' => 'Anderer',
    );
    
    public function fcIdealoIsTokenCorrect() 
    {
        $blSuccess = false;

        $oClient = fcidealo_base::getClient();
        $aOrders = $oClient->getOrders();
        if(is_array($aOrders) && count($aOrders) > 0 || $oClient->getHttpStatus() == 200) {
            $blSuccess = true;
        }
        return $blSuccess;
    }
    
    public function fcIdealoCheckToken() {
        $oConfig = oxRegistry::getConfig();
        $sToken = $oConfig->getShopConfVar('sIdealoToken');
        if(!empty($sToken)) {        
            $blSuccess = $this->fcIdealoIsTokenCorrect();
            
            if($blSuccess === true) {
                $sMessage = '<span style="margin-left:20px;color:green;">'.oxRegistry::getLang()->translateString('FCIDEALO_TOKEN_SUCCESS').'</span>';
            } else {
                $sMessage = '<span style="margin-left:20px;color:red;">'.oxRegistry::getLang()->translateString('FCIDEALO_TOKEN_ERROR').'</span>';
            }
            return $sMessage;
        }
    }
    
    public function fcIdealoGetShopPaymentTypes()
    {
        $aPaymentsTypes = array();
        
        $sTable = getViewName('oxpayments');
        $sQuery = "SELECT oxid, oxdesc FROM {$sTable} ORDER BY oxsort, oxdesc";
        $oResult = oxDb::getDb()->Execute($sQuery);
        if ($oResult != false && $oResult->recordCount() > 0) {
            while (!$oResult->EOF) {
                $aPaymentsTypes[$oResult->fields[0]] = $oResult->fields[1];
                $oResult->moveNext();
            }
        }
        return $aPaymentsTypes;
    }
    
    public function fcIdealoGetShopDeliveryTypes()
    {
        $aDeliveryTypes = array();
        
        $sTable = getViewName('oxdeliveryset');
        $sQuery = "SELECT oxid, oxtitle FROM {$sTable} ORDER BY oxpos, oxtitle";
        $oResult = oxDb::getDb()->Execute($sQuery);
        if ($oResult != false && $oResult->recordCount() > 0) {
            while (!$oResult->EOF) {
                $aDeliveryTypes[$oResult->fields[0]] = $oResult->fields[1];
                $oResult->moveNext();
            }
        }
        return $aDeliveryTypes;
    }
    
    public function fcGetIdealoPaymentTypes() 
    {
        $oClient = fcidealo_base::getClient();
        $aTypes = $oClient->getSupportedPaymentTypes();
        if(!is_array($aTypes) || count($aTypes) == 0) {
            $aTypes = $this->_aDefaultIdealoPaymentTypes;
        }
        return $aTypes;
    }
    
    public function fcGetIdealoDeliveryTypes() {
        return $this->_aDefaultIdealoDeliveryTypes;
    }
    
    public function fcGetIdealoDeliveryCarriers() {
        $aCarriers = $this->_aDefaultDeliveryCarriers;
        $aCarriers['other'] = oxRegistry::getLang()->translateString('FCIDEALO_OTHER');
        return $this->_aDefaultDeliveryCarriers;
    }
    
    public function _serializeConfVar($sType, $sName, $mValue)
    {
        if($sName == 'sIdealoPaymentMap' || $sName == 'sIdealoDeliveryMap') {
            return serialize($mValue);
        } else {
            return parent::_serializeConfVar($sType, $sName, $mValue);
        }
    }
    
    public function _loadMetadataConfVars($aModuleSettings)
    {
        $aReturn = parent::_loadMetadataConfVars($aModuleSettings);
        if($this->_sModuleId == 'fcidealo') {
            if(isset($aReturn['vars']['select']['sIdealoPaymentMap'])) {
                $aPaymentMap = unserialize(html_entity_decode($aReturn['vars']['select']['sIdealoPaymentMap']));
                $aReturn['vars']['select']['sIdealoPaymentMap'] = $aPaymentMap;
            }
            if(isset($aReturn['vars']['select']['sIdealoDeliveryMap'])) {
                $aDeliveryMap = unserialize(html_entity_decode($aReturn['vars']['select']['sIdealoDeliveryMap']));
                $aReturn['vars']['select']['sIdealoDeliveryMap'] = $aDeliveryMap;
            }
        }
        return $aReturn;
    }
    
    public function fcIdealoIsConfigComplete()
    {
        if(!$this->fcIdealoTokenMissing() && !$this->fcIdealoEmailMissing() && $this->fcIdealoIsTokenCorrect()) {
            return true;
        }
        return false;
    }
    
    public function fcIdealoTokenMissing() 
    {
        $sToken = oxRegistry::getConfig()->getShopConfVar('sIdealoToken');
        return empty($sToken);
    }
    
    public function fcIdealoEmailMissing() 
    {
        $sEmail = oxRegistry::getConfig()->getShopConfVar('sIdealoEmail');
        return empty($sEmail);
    }

}
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


class fcidealo_base extends oxBase
{

    protected static $oClient = null;
    protected $aIdealoCache = null;
    protected $sLogFileName = 'fcIdealo.log';
    
    public static function getClient()
    {
        if(self::$oClient === null) {
            require_once dirname(__FILE__).'/../sdk/autoload.php';
            //require_once dirname(__FILE__).'/../sdk/idealo/Direktkauf/REST/Client.php';
            $oConfig = oxRegistry::getConfig();
            $sToken = $oConfig->getShopConfVar('sIdealoToken');
            $blIsLive = $oConfig->getShopConfVar('sIdealoMode') == 'live' ? true : false;

            self::$oClient = new idealo\Direktkauf\REST\Client($sToken, $blIsLive);
        }
        return self::$oClient;
    }
    
    protected function insertRecord($aData, $sTable)
    {
        if ($sTable && is_array($aData) && sizeof($aData) > 0) {            
            $aFields = array_keys($aData);
            
            $sQuery = "INSERT INTO {$sTable} (".implode(',', $aFields).") VALUES(".implode(", ", oxDb::getInstance()->quoteArray($aData)).")";
            oxDb::getDb()->Execute($sQuery);
            return true;
        }
        return false;
    }
    
    protected function sendShopOrderNr($sIdealoOrderNr, $sShopOrderNr, $sOxid) 
    {
        $oClient = fcidealo_base::getClient();
        $blSuccess = $oClient->sendOrderNr($sIdealoOrderNr, $sShopOrderNr);
        if($blSuccess === false && $oClient->getCurlError() != '') {
            $this->sendShopOrderErrorMail($sIdealoOrderNr, $sShopOrderNr, $oClient);
        } else {
            $sQuery = "UPDATE oxorder SET FCIDEALO_ORDERNR_SENT = NOW() WHERE oxid = '{$sOxid}'";       
            oxDb::getDb()->Execute($sQuery);
            
            $this->writeLogEntry('Sent oxid order-nr '.$sShopOrderNr.' for idealo order-nr: '.$sIdealoOrderNr);
        }
    }
    
    protected function sendOrderRevocation($sIdealoOrderNr, $sOptionalReason = '') 
    {
        $oClient = fcidealo_base::getClient();
        $sResponse = $oClient->sendRevocationStatus($sIdealoOrderNr, $sOptionalReason);
        if($sResponse === false && $oClient->getCurlError() != '') {
            $this->sendRevocationErrorMail($sIdealoOrderNr, $oClient);
        } else {
            $this->writeLogEntry('Sended revoke status to Idealo for IdealoOrderNr'.$sIdealoOrderNr);
        }
    }

    public function getCacheValue($sName, $sScope = 'default')
    {
        if (isset($this->aIdealoCache[$sScope][$sName])) {
            return $this->aIdealoCache[$sScope][$sName];
        }
        return null;
    }

    public function setCacheValue($sName, $sValue, $sScope = 'default')
    {
        $this->aIdealoCache[$sScope][$sName] = $sValue;     
    }
    
    protected function sendShopOrderErrorMail($sIdealoOrderNr, $sShopOrderNr, $oClient)
    {
        $sSubject = "Idealo order-nr request had an error";
        $sText  = "The cronjob tried to send the shop-order-nr {$sShopOrderNr} to idealo for the following idealo order-nr: {$sIdealoOrderNr}\n";
        $sText .= "but an error occured:\n\n";
        $sText .= "Curl-error: {$oClient->getCurlError()}\n";
        $sText .= "Curl-error-nr: {$oClient->getCurlErrno()}\n";
        $sText .= "HTTP-code: {$oClient->getHttpStatus()}\n";
        $this->sendErrorMail($sSubject, $sText);
    }
    
    protected function sendFulfillmentErrorMail($sIdealoOrderNr, $oClient) 
    {
        $sSubject = "Idealo fulfillment request had an error";
        $sText  = "The cronjob tried to send the fulfillment status to idealo for the following idealo order-nr: {$sIdealoOrderNr}\n";
        $sText .= "but an error occured:\n\n";
        $sText .= "Curl-error: {$oClient->getCurlError()}\n";
        $sText .= "Curl-error-nr: {$oClient->getCurlErrno()}\n";
        $sText .= "HTTP-code: {$oClient->getHttpStatus()}\n";
        $this->sendErrorMail($sSubject, $sText);
    }
    
    protected function sendRevocationErrorMail($sIdealoOrderNr, $oClient) 
    {
        $sSubject = "Idealo revocation request had an error";
        $sText  = "The cronjob tried to send the revocation status to idealo for the following idealo order-nr: {$sIdealoOrderNr}\n";
        $sText .= "but an error occured:\n\n";
        $sText .= "Curl-error: {$oClient->getCurlError()}\n";
        $sText .= "Curl-error-nr: {$oClient->getCurlErrno()}\n";
        $sText .= "HTTP-code: {$oClient->getHttpStatus()}\n";
        $this->sendErrorMail($sSubject, $sText);
    }
    
    protected function sendProductNotFoundMail($aItem)
    {
        $sSubject = "Idealo product could not be found in shop";
        $sText = "The product with the SKU {$aItem['sku']} and the title {$aItem['title']} could not be found in the shop!";
        $this->sendErrorMail($sSubject, $sText);
    }
    
    protected function sendGetOrdersErrorMail($oClient)
    {
        $sSubject = "Idealo orders could not be requested";
        $sText  = "Tried to request the orders from ideale but an error occured:\n\n";
        $sText .= "Curl-error: {$oClient->getCurlError()}\n";
        $sText .= "Curl-error-nr: {$oClient->getCurlErrno()}\n";
        $sText .= "HTTP-code: {$oClient->getHttpStatus()}\n";
        $this->sendErrorMail($sSubject, $sText);
    }

    protected function sendHandleOrderError( $aData, $sErrorTypeMessage='' )
    {
        $sSubject = "Idealo order could not be handled - ".$sErrorTypeMessage;
        $sText  = "An error of type ".$sErrorTypeMessage." occured while trying to process order with idealo order nr: ".$aData['order_number']."\n\n";
        $sText .= "Here's the full Data array of that order:\n".print_r( $aData, true );
        $this->sendErrorMail($sSubject, $sText);
    }
    
    /**
     * Method informs about an exception that has been catched via mail
     * 
     * @param object $oEx
     * @return void
     */
    protected function sendExceptionMail( $oEx, $sMethod='' ) {
        $sMethodAddition = ( $sMethod ) ? "(".$sMethod.")" : "";
        $sSubject = "Idealo Exception occured";
        $sText  = "While trying to perform a method ".$sMethodAddition." the following exception:\n\n";
        $sText .= $oEx->getMessage();
        $this->sendErrorMail( $sSubject, $sText );
    }

    protected function writeLogEntry($sMessage) {
        if ((bool)oxRegistry::getConfig()->getShopConfVar('sIdealoLoggingActive')) {
            $sMessage = date("Y-m-d h:i:s")." ".$sMessage." \n";
            
            $sLogFilePath = getShopBasePath().'/log/'.$this->sLogFileName;
            $oLogFile = fopen($sLogFilePath, "a");
            fwrite($oLogFile, $sMessage);
            fclose($oLogFile);
        }
    }
    
    protected function sendErrorMail($sSubject, $sText) 
    {
        $this->writeLogEntry($sText);
        $sText .= '('.date('Y-m-d H:i:s').')';
        $sErrorMail = oxRegistry::getConfig()->getShopConfVar('sIdealoEmail');
        if ($sErrorMail) {
            mail($sErrorMail, $sSubject, $sText);
        }
    }

}
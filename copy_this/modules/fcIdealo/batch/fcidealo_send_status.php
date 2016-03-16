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


class fcidealo_send_status extends fcidealo_base
{

    protected function handleFulfillment($aOrderInfo) 
    {   
        $oClient = fcidealo_base::getClient();

        $blSuccess = false;
        try {
            $blSuccess = $oClient->sendFulfillmentStatus($aOrderInfo['fcidealo_ordernr'], $aOrderInfo['oxtrackcode'], $aOrderInfo['fcidealo_delivery_carrier']);
        } 
        catch ( Exception $oEx ) {
            $this->sendExceptionMail( $oEx. 'script: fcidealo_send_status::handleFulfillment()' );
        }
        
        if($blSuccess === false && $oClient->getCurlError() != '') {
            $this->sendFulfillmentErrorMail($aOrderInfo['fcidealo_ordernr'], $oClient);
        } else {
            if(!empty($aOrderInfo['oxtrackcode'])) {
                $sQuery = "UPDATE oxorder SET FCIDEALO_FULFILLMENT_SENT = NOW(), FCIDEALO_TRACKINGCODE_SENT = NOW() WHERE oxid = '{$aOrderInfo['oxid']}'";
            } else {
                $sQuery = "UPDATE oxorder SET FCIDEALO_FULFILLMENT_SENT = NOW() WHERE oxid = '{$aOrderInfo['oxid']}'";
                $aOrderInfo['fcidealo_delivery_carrier'] = ''; // removing it only for logging purposes
            }
            oxDb::getDb()->Execute($sQuery);
            
            $this->writeLogEntry('Sent fulfillment status for idealo order-nr: '.$aOrderInfo['fcidealo_ordernr'].' trackcode: '.$aOrderInfo['oxtrackcode'].' carrier: '.$aOrderInfo['fcidealo_delivery_carrier']);
        }
    }
    
    protected function handleRevocation($aOrderInfo)
    {
        $sReason = oxRegistry::getConfig()->getShopConfVar('sIdealoStornoReason');
        
        /**
         * If order has been already sent storno reason is fixed to RETOUR
         * @see https://tickets.fatchip.de/view.php?id=21081#c72425
         */
        if ( $aOrderInfo['oxsenddate'] != '0000-00-00 00:00:00' ) {
            $sReason = "RETOUR";
        }
        
        $oClient = fcidealo_base::getClient();
        
        $blSuccess = false;
        try {
            $blSuccess = $oClient->sendRevocationStatus( $aOrderInfo['fcidealo_ordernr'], $sReason );
        } 
        catch ( Exception $oEx ) {
            $this->sendExceptionMail( $oEx. 'script: fcidealo_send_status::handleRevocation()' );
        }
        
        if($blSuccess === false && $oClient->getCurlError() != '') {
            $this->sendRevocationErrorMail($aOrderInfo['fcidealo_ordernr'], $oClient);
        } else {
            $sQuery = "UPDATE oxorder SET FCIDEALO_REVOCATION_SENT = NOW() WHERE oxid = '{$aOrderInfo['oxid']}'";       
            oxDb::getDb()->Execute($sQuery);
            
            $this->writeLogEntry('Sent revocation status for idealo order-nr: '.$aOrderInfo['fcidealo_ordernr']);
        }
    }
    
    protected function getFulfilledOrders()
    {
        $aOrders = array();
        
        $sQuery = "SELECT oxid, fcidealo_ordernr, oxtrackcode, fcidealo_delivery_carrier FROM oxorder WHERE fcidealo_ordernr != '' AND ((oxsenddate != '0000-00-00 00:00:00' AND fcidealo_fulfillment_sent = '0000-00-00 00:00:00') OR (oxtrackcode != '' AND fcidealo_trackingcode_sent = '0000-00-00 00:00:00'))";
        $oResult = oxDb::getDb()->Execute($sQuery);
        if ($oResult != false && $oResult->recordCount() > 0) {
            while (!$oResult->EOF) {
                $aOrders[] = array(
                    'oxid' => $oResult->fields[0],
                    'fcidealo_ordernr' => $oResult->fields[1],
                    'oxtrackcode' => $oResult->fields[2],
                    'fcidealo_delivery_carrier' => $oResult->fields[3],
                );
                $oResult->moveNext();
            }
        }
        return $aOrders;
    }
    
    protected function getRevocationOrders()
    {
        $aOrders = array();

        $sQuery = "SELECT oxid, fcidealo_ordernr, oxsenddate FROM oxorder WHERE fcidealo_ordernr != '' AND oxstorno = '1' AND fcidealo_revocation_sent = '0000-00-00 00:00:00'";
        $oResult = oxDb::getDb()->Execute($sQuery);
        if ($oResult != false && $oResult->recordCount() > 0) {
            while (!$oResult->EOF) {
                $aOrders[] = array(
                    'oxid' => $oResult->fields[0],
                    'fcidealo_ordernr' => $oResult->fields[1],
                    'oxsenddate' => $oResult->fields[2],
                );
                $oResult->moveNext();
            }
        }
        return $aOrders;
    }
    
    protected function getUnsentOrderNumbers()
    {
        $aOrders = array();
        
        $sQuery = "SELECT oxid, oxordernr, fcidealo_ordernr FROM oxorder WHERE fcidealo_ordernr != '' AND fcidealo_ordernr_sent = '0000-00-00 00:00:00'";
        $oResult = oxDb::getDb()->Execute($sQuery);
        if ($oResult != false && $oResult->recordCount() > 0) {
            while (!$oResult->EOF) {
                $aOrders[] = array(
                    'oxid' => $oResult->fields[0],
                    'oxordernr' => $oResult->fields[1],
                    'fcidealo_ordernr' => $oResult->fields[2],
                );
                $oResult->moveNext();
            }
        }
        return $aOrders;
    }
    
    protected function handleFulfillments()
    {
        $aFulfillmentInfo = $this->getFulfilledOrders();
        foreach ($aFulfillmentInfo as $aOrderInfo) {
            $this->handleFulfillment($aOrderInfo);
        }
    }
    
    protected function handleRevocations()
    {
        $aRevocationInfo = $this->getRevocationOrders();
        foreach ($aRevocationInfo as $aOrderInfo) {
            $this->handleRevocation($aOrderInfo);
        }
    }
    
    protected function sendOrderNumbers()
    {
        $aOrderNrInfo = $this->getUnsentOrderNumbers();
        foreach ($aOrderNrInfo as $aOrderInfo) {
            $this->sendShopOrderNr($aOrderInfo['fcidealo_ordernr'], $aOrderInfo['oxordernr'], $aOrderInfo['oxid']);
        }
    }
    
    public function start()
    {
        $this->handleFulfillments();
        $this->handleRevocations();
        $this->sendOrderNumbers();
    }

}
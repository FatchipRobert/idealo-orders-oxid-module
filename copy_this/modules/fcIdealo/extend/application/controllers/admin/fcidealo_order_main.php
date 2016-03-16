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

class fcidealo_order_main extends fcidealo_order_main_parent {
    
    public function save() {
        $sOxid = $this->getEditObjectId();
        $aParams = oxRegistry::getConfig()->getRequestParameter("editval");

        $oOrder = oxNew( "oxorder" );
        if ( $sOxid != "-1" ) {
            $oOrder->load( $sOxid );
            
            $sCurrentTrackCode      = $oOrder->oxorder__oxtrackcode->value;
            $sNewCurrentTrackCode   = $aParams['oxorder__oxtrackcode'];
            
            if ( !empty( $sNewCurrentTrackCode ) && $sCurrentTrackCode != $sNewCurrentTrackCode ) {
                // track code has been changed so order needs to be sent again
                $this->_fcResetFulfillmentSent( $sOxid );
            }
        }        
        
        parent::save();
    }
    
    
    /**
     * Resets fulfillment send
     * 
     * @param string $sOxid
     * @return void
     */
    protected function _fcResetFulFillmentSent( $sOxid ) {
        $oDb = oxDb::getDb();
        $sQuery = "
            UPDATE oxorder SET fcidealo_fulfillment_sent='0000-00-00 00:00:00', fcidealo_trackingcode_sent = '0000-00-00 00:00:00' WHERE OXID='".$sOxid."' LIMIT 1
        ";
        $oDb->Execute( $sQuery );
    }
}

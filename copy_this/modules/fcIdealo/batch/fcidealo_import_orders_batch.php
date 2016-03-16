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

 

// load OXID components
if ( !function_exists( 'getShopBasePath' ) ) {
	function getShopBasePath() {
		return dirname(__FILE__).'/../../../';
	}
}
if ( !file_exists( getShopBasePath() . "/bootstrap.php" ) ) {
	function isAdmin() {
		return false;
	}
}

set_time_limit(0);
ini_set("memory_limit", "6000M");

error_reporting( E_ALL ^ E_NOTICE );

if($_SERVER) {
    if(array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) === false){
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
    }
    if(array_key_exists('HTTP_HOST', $_SERVER) === false){
        $_SERVER['HTTP_HOST'] = 'localhost';
    }
}
if ( file_exists( getShopBasePath() . "/bootstrap.php" ) ) {
	require_once getShopBasePath() . "/bootstrap.php";
} else {
    require getShopBasePath() . 'modules/functions.php';
    require_once getShopBasePath() . 'core/oxfunctions.php';
}

$oScript = oxNew('fcidealo_import_orders');
$oScript->start();
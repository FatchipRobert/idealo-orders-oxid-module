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

/**
 * Metadata version
 */
$sMetadataVersion = '1.1';

$aModule = array(
    'id'            => 'fcidealo',
    'title'         => 'idealo Direktkauf OXID',
    'description'   => '',
    'thumbnail'     => 'fc_idealo_direktkauf_oxid_produktbox.png',
<<<<<<< HEAD
    'version'       => '1.0.9_6333',
=======
    'version'       => '1.0.8_6008',
>>>>>>> refs/remotes/idealo/master
    'author'        => 'idealo internet GmbH',
    'url'           => '',
    'extend'        => array(
        'module_config' => 'fcIdealo/extend/application/controllers/admin/fcidealo_module_config',
        'order_main'    => 'fcIdealo/extend/application/controllers/admin/fcidealo_order_main',
    ),
    'files' => array(
        'fcidealo_base' => 'fcIdealo/batch/fcidealo_base.php',
        'fcidealo_import_orders' => 'fcIdealo/batch/fcidealo_import_orders.php',
        'fcidealo_send_status' => 'fcIdealo/batch/fcidealo_send_status.php',
    ),
    'templates' => array(
    ),
    'blocks' => array(
        array(
            'template' => 'module_config.tpl',
            'block' => 'admin_module_config_var',
            'file' => 'fcidealo_module_config_var',
        ),
        array(
            'template' => 'order_overview.tpl',
            'block' => 'admin_order_overview_folder_form',
            'file' => 'fcidealo_ordernr',
        )
    ),
    'settings' => array(
        array('group' => 'FCIDEALO_GENERAL', 'name' => 'sIdealoMode', 'type' => 'select',  'value' => 'test', 'constrains' => 'live|test', 'position' => 10),
        array('group' => 'FCIDEALO_GENERAL', 'name' => 'sIdealoToken', 'type' => 'password',  'value' => '', 'position' => 20),
        array('group' => 'FCIDEALO_GENERAL', 'name' => 'sIdealoEmail', 'type' => 'str',  'value' => '', 'position' => 30),
        array('group' => 'FCIDEALO_GENERAL', 'name' => 'sIdealoStornoReason', 'type' => 'select',  'value' => 'MERCHANT_DECLINE', 'constrains' => 'CUSTOMER_REVOKE|MERCHANT_DECLINE|RETOUR', 'position' => 40),
        array('group' => 'FCIDEALO_GENERAL', 'name' => 'sIdealoLoggingActive', 'type' => 'bool', 'value' => '1', 'position' => 50),
        array('group' => 'FCIDEALO_PAYMENT_MAP', 'name' => 'sIdealoPaymentMap', 'type' => 'select',  'value' => '', 'position' => 10),
        array('group' => 'FCIDEALO_DELIVERY_MAP', 'name' => 'sIdealoDeliveryMap', 'type' => 'select',  'value' => '', 'position' => 10),
        array('group' => 'FCIDEALO_CONFIG_TEST', 'name' => 'sIdealoConfigTest', 'type' => 'str',  'value' => '', 'position' => 10),
    )
);

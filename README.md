## Title
idealo Direktkauf OXID

## License
Apache License 2.0, see LICENSE

## Prefix
fc

## Version
1.0.5_5146
January 6 2016

## Link
http://www.idealo.de

## Requirements
libcurl 

## Description
With this module you can import your idealo-orders into your oxid-shop.

## Extend
void

## Installation
The update-process is exactly the same!

1. Extract the module-package.
2. Copy the content of the folder `copy_this` into your shop root-folder (where `config.inc.php` is located).
3. Execute SQL changes in `install.sql`.
4. In the admin-interface of OXID-Shop you go to Service->Tools and press the button "Update Views now".
5. Go to Extensions->Modules, select the "idealo Direktkauf OXID" extension and press the "Activate" button in the "Overview" tab.
6. Empty "tmp" folder.
7. Go to Extensions->Modules, select the "idealo Direktkauf OXID" extension and configure the module in the "Settings" tab.

8. Set cronjob for script `YOUR_SHOP/modules/fcIdealo/batch/fcidealo_import_orders_batch.php`
   This script imports the idealo orders.
   We recommend setting this to something between 10 minutes and 1 hour.
   
9. Set cronjob for script `YOUR_SHOP/modules/fcIdealo/batch/fcidealo_send_status_batch.php`
   This script sends the fulfillment- and revocation-status to idealo. It also sends the shop-order-nr when this didn't work the first time.
   We recommend setting this to once every hour.

## Deinstallation

1. Go to Extensions->Modules, select the "idealo Direktkauf OXID" extension and press the "Deactivate" Button in the "Overview" tab.

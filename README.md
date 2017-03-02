# idealo Direktkauf OXID eShop plugin

## License
Apache License 2.0, see LICENSE

## Prefix
fc

## Version
1.0.9_6333
November 30 2016

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

## OXID Enterprise Edition:
When using the OXID Enterprise Edition, you have to add both cronjobs for EVERY subshop that uses the module.
You have to add the shop-id as parameter to the script.
When your cronjob is triggered over crontab or something similar just execute the script in the following way:
"YOUR_SHOP/modules/fcIdealo/batch/fcidealo_import_orders_batch.php 4" for the shop-id 4
When your cronjob is triggered over web then add the parameter to the URL in the following way:
"http://YOUR_SHOP/modules/fcIdealo/batch/fcidealo_import_orders_batch.php?shopid=4" for the shop-id 4

## De-installation

1. Go to Extensions->Modules, select the "idealo Direktkauf OXID" extension and press the "Deactivate" Button in the "Overview" tab.

## How to contribute
If you want to contribute to *idealo Direktkauf XT-Commerce Plugin* either open up an issue or fork the repository and make a pull request with your changes.

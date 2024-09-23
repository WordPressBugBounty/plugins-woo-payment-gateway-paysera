WooCommerce Payment Gateway - Paysera
=======================

Version: 3.5.7

Date: 2024-09-18

Contributors: Paysera

Tags: online payment, payment, payment gateway, international payments, shipping

Requires at least: 4.0

Tested up to: 6.6

Stable tag: 3.5.7

Requires PHP: 7.4

Requires PHP Extension: BCMath, Zip

Minimum requirements: WooCommerce 5.0

License: GPLv3

License URL: http://www.gnu.org/licenses/gpl-3.0.html

Description
-----------
Paysera payments + delivery

With this one plugin you will receive everything your e-shop needs in one place – payment gateway to accept online payments and delivery options via all popular couriers displayed on your website.

In order to receive full benefits of both Paysera Payment and Delivery plugins, please use the outlined links to access our detailed how-to instructions.

1. Paysera Payments
   This service included in the plugin enables you to accept online payments via cards, SMS, or the most popular banks in your country. It is used by thousands of online merchants across Europe, and is easy to integrate and manage.
   Read more about Paysera Checkout - https://www.paysera.com/v2/en/payment-gateway-checkout
   Instructions - https://developers.paysera.com/en/checkout/basic

2. Paysera Delivery
   This service, that is also included in this plugin displays different delivery companies that your buyers can choose from when ordering your products. No need to sign separate agreements with couriers or overcome several different integrations – we have done it for you. Enjoy low delivery prices and quick support when needed.
   Read more about Paysera Delivery - https://www.paysera.com/v2/en/checkout-delivery-service
   Instructions - https://developers.paysera.com/en/delivery/

Features and benefits
-----------
- One plugin for integration of different payment methods: cards, SMS, online banking, more.
- One plugin for integration of different courier services: Omniva, Venipak, LP Express, TNT, and others.
- Integration takes up to 5 min (if you are already a Paysera client).
- One agreement for all the couriers and all banks.
- Easy to manage: turn couriers and payment methods ON and OFF as you like.
- Everything in one place – Paysera system: see all the deliveries by different couriers, receive payments via different banks and payment methods in the same system.

Logging
-----------
To keep track of the plugin's errors, default log level for payment and delivery is set to 'Error'.
The logs can be viewed and downloaded from the plugin 'Extra Settings' page. To download the zip, php zip extension is required. 
Log files can be deleted from the 'WooCommerce -> Status -> Logs' page. Available logging levels are:
 - None: it disables logging and no logs are saved.
 - Error: only plugin errors are saved.
 - Info: plugin errors and related debug information are saved.

Fees
-----------
For information regarding Paysera fees please visit:
Paysera Delivery fees - https://www.paysera.com/v2/en/fees/checkout-delivery
Paysera Checkout fees - https://www.paysera.com/v2/en/fees/payment-gateway-fees

Support
-----------
Paysera Client Support in English is available 24/7!
+44 20 80996963
support@paysera.com

During working hours support is available in 12 languages.
Contact us - https://www.paysera.lt/v2/en/contacts

For the latest news about the Paysera services – follow us on Facebook (https://www.facebook.com/paysera.international/) and Twitter (https://twitter.com/paysera.
Get notifications about our operational status – subscribe to our status page (https://paysera.freshstatus.io/).

About Paysera
-----------
Paysera (https://www.paysera.com/v2/en-GB/paysera-account) is a global fintech company providing financial and related services to clients from all over the world since 2004.

Explore other Paysera services:
- currency exchange (https://www.paysera.com/v2/en/fees/currency-conversion-calculator#/) at competitive rates;
- instant euro and cheap international transfers (https://www.paysera.com/v2/en/international-transfers);
- LT, BG, and RO IBANs (https://www.paysera.com/v2/en/blog/iban-account) for business and private clients;
- visa cards (https://www.paysera.com/v2/en/payment-card-visa) that are compatible with Google Play (https://www.paysera.com/v2/en/blog/googlepay-samsungpay) and Apple Pay (https://www.paysera.com/v2/en/apple-pay), and so much more.

All the main services can be easily managed via the Paysera mobile app (https://www.paysera.com/v2/en-GB/mobile-application), which is available to download from the App Store (https://apps.apple.com/us/app/paysera-mobile-wallet/id737308884), Google Play (https://play.google.com/store/apps/details?id=lt.lemonlabs.android.paysera), and Huawei AppGallery (https://appgallery.huawei.com/#/app/C103007513).

Installation
------------
Follow video tutorial or instructions below.

https://www.youtube.com/watch?v=ojNf_P4gwPQ

Installation by FTP:

1. Download Paysera plugin zip.

2. Connect to server and go to WordPress base directory.

3. Create New Folder and name it 'Paysera' in:
    /wp-content/plugins

4. Extract files and directories from zip file to newly created 'Paysera' folder.

5. Activate Paysera plugin:
    Plugins -> Installed Plugins -> Paysera Payment And Delivery -> Activate

6. Configure Paysera plugin in:
    Paysera -> Payments
    Paysera -> Delivery

    Enter checkout project id, password and other required information.

7. Save changes.


Installation from admin panel:

1. Download Paysera plugin zip.

2. Connect to WordPress admin panel.

3. Install Paysera plugin to WordPress:
    Plugins -> Add New -> Upload Plugin -> Choose File -> Choose downloaded zip -> Install Now

4. Activate Paysera plugin:
    Plugins -> Installed Plugins -> Paysera Payment And Delivery -> Activate

5. Configure Paysera plugin in:
    Paysera -> Payments
    Paysera -> Delivery

   Enter checkout project id, password and other required information.

6. Save changes.


Installation from admin panel (marketplace):

1. Connect to WordPress admin panel.

2. Install Paysera plugin to WordPress:

    2.1. Plugins -> Add New;

    2.2. Find 'WooCommerce Payment Gateway - Paysera';

    2.3. Install.

4. Activate Paysera plugin:
    Plugins -> Installed Plugins -> Paysera Payment And Delivery -> Activate

5. Configure Paysera plugin in:
    Paysera -> Payments
    Paysera -> Delivery

   Enter checkout project id, password and other required information.

6. Save changes.

Changelog
---------
= 3.5.7 =
* Fix error on Classic Checkout page

= 3.5.6 =
* Update - Prefer shipping phone number over billing phone number for delivery orders
* Fix - Fix "lodash" error with new Woocommerce version started from 9.2.0
* Improvement - Message about changing the terminal`s location was added to the info log
* Fix - Inappropriate shipping gateway is hidden on cart and checkout pages even when 'Hide shipping methods' setting is Disabled
* Fix - Changing of the phone number is logged while it was not changed
* Fix - min/max weight validations are not working on new checkout page

= 3.5.5 =
* Fix - Fix errors in Delivery plugin
* Improvement - Add validation errors text into callback logs

= 3.5.4 =
* Fix - Order house number field gets added from session when field is disabled
* Fix - Logo alignment near payment method title
* Fix - "Undefined country code IS02" error at checkout
* Fix - Fix the List of payment methods visibility
* Fix - Fix of the Delivery notes in WooCommerce order notes
* Improvement - Optimized Paysera plugin queries speed.
* Improvement - Optimized plugin CSS, prevented it affect the shipping methods and options

= 3.5.3 =
* Fix - Errors with PHP 8.2 version
* Fix - Shipping logo displayed incorrect in grid view (Mobile)
* Improve CSS selectors: remove all "!important" tags
* New - Add Enable/Disable button for Delivery settings
* Remove JS and CSS enqueued files from other unnecessary pages
* Added form validations in the Shipping gateway settings window in WooCommerce shipping settings for Paysera's delivery gateways
* Fix - Delivery gateway options received default values after migrating to versions 3.5.*
* Fix - Order requires a shipping option error for WooCommerce 8.8 versions
* Fix - Shipping method configuration values are taken from latest shipping method instead of selected method for same courier company
* Update - Added synchronisation between WC orders and Delivery API
* Fix - After updating, plugin becomes deactivated and it's delivery settings are reset to default values

= 3.5.2 =
* Update - Added logging functionality for payment and delivery
* Update - Delivery order gets created in checkout page instead of thank you page

= 3.5.1 =
* Update - Delivery order creation process improvements

= 3.5.0 =
* Fix - Payment method list would be visible for Quipu
* Fix - Selected payment method reset issue after changing payment country
* Fix - City selection triggered by different spellings
* Fix - Compatibility with WooCommerce 8.5.1
* Fix - Duplicate delivery order issue on some edge cases
* New - Paysera Payment support for Block based checkout
* New - Paysera Delivery support for Block based checkout

= 3.4.3 =
* Update - Pass extra information to payment request

= 3.4.2 =
* Update - WordPress version compatibility with 6.4.2
* Update - Itella, TNT delivery gateway added with courier methods only

= 3.4.1 =
* Update - Shipping fee is excluded when checking for available payment methods

= 3.4.0 =
* Update - WordPress tested upto 6.3
* Update - Woocommerce tested upto 8.0
* Update - Don't load Paysera plugin if Woocommerce is not activated
* Update - Show a Woocommerce plugin dependency notice if plugin is not activated
* Fix - Fixed payment method list for Quipu
* Fix - Fixed deactivating plugin while Woocommerce is not activated
* Fix - Fixed strict composer PHP version policy for our plugin

= 3.3.5 =
* Fix - Delivery terminal cities not being loaded after country selection

= 3.3.4 =
* Update - Updated PHP version to 7.4
* Fix - Fixed security vulnerabilities for plugin
* Fix - Paysera delivery terminal selections getting cleared on checkout page due to refresh

= 3.3.3 =
* Fix - Guzzle library incompatibility fixed

= 3.3.2 =
* Fix - Payment plugin get enabled without Project ID

= 3.3.1 =
* Update - Webtopay library supported upto PHP 8.1
* Fix - Deprecation warnings fixed for PHP 8.1

= 3.3.0 =
* Update - Added plugin information on payment request

= 3.2.9 =
* Update - Added hint for product dimensions if paysera shipping methods are enabled

= 3.2.8 =
* Fix - Warning about cache helper file import

= 3.2.7 =
* Update - WordPress tested upto 6.2
* Fix - Paysera delivery gateways displayed if plugin is not active or gateway is disabled
* Fix - Checkout page default country selection logic
* Update - Project ID negative value prevention
* Update - Invalid project credential validation on delivery settings

= 3.2.6 =
* Fix - Plugin deactivation logic
* Update - Min requirements raised to PHP 7.2

= 3.2.5 =
* Fix - Empty product width, weight, height, length error fix in php >= 8.0
* Fix - Terminal selection visibility bug
* Fix - Selected countries not displaying in list of payment methods
* Fix - Class autoload critical error
* Update - Add compatibility with WooCommerce High-Performance Order Storage
* Update - Select2 js and css optimization

= 3.2.4 =
* Fix - Min/Max weight error fix
* Fix - Math operations with incorrect data types
* Update - Validation of Paysera project fields

= 3.2.3 =
* Update - House field addition to order view in admin

= 3.2.2 =
* Fix - Callback URL generating fix

= 3.2.1 =
* Update - Company logo update

= 3.2.0 =
* Update - General improvements
* Fix - Code style fixes
* Update - Hide shipping methods functionality
* Update - Delivery methods error rework
* Update - Payment List enabled by default

= 3.1.9 =
* Fix - Delivery terminal and payment method selection fixes
* Update - Payment methods css fix
* Update - Plugin description improvements

= 3.1.8 =
* Update - Product weight and dimensions calculation improvements

= 3.1.7 =
* Fix - Notice dismiss button fix
* Fix - Unsupported operand types fix
* Fix - Foreach usage fix
* Update - Auto select delivery terminal country and city

= 3.1.6 =
* Update - Prefixing namespaces to avoid conflicts
* Update - Eshop order id addition to delivery order
* Fix - Delivery order receiver address updates for terminal delivery methods
* Fix - Notice dismiss button rework
* Update - Delivery methods grid view option

= 3.1.5 =
* Fix - Payment methods grid display fix
* Update - Terminal country selection improvement
* Update - Legal name addition to delivery order
* Fix - Delivery calls not made if delivery is not enabled

= 3.1.4 =
* Update - Additional error logging
* Fix - Lang parameter fix

= 3.1.3 =
* Update - Additional order notes
* Fix - Some old settings were loaded incorrectly
* Update - Composer requirements cleanup

= 3.1.2 =
* Update - Settings backwards compatibility
* Update - Additional check for duplicate plugins
* Fix - Payment logo fix
* Update - Composer improvement

= 3.1.1 =
* Update - Payment methods style improvements
* Fix - Delivery dimensions fix

= 3.1.0 =
* Fix - Plugin name fix

= 3.0.9 =
* Update - Composer file

= 3.0.8 =
* Update - Payment enable/disable functionality improvement

= 3.0.7 =
* Fix - Checkout logo size fix

= 3.0.6 =
* Fix - Order creation error fix
* Update - Terminal fields improvement
* Update - Order notes improvements

= 3.0.5 =
* Fix - Delivery validation error fix
* Update - Payment settings functionality improvements

= 3.0.4 =
* Update - Hooks and naming update

= 3.0.3 =
* Update - Translations refactor
* Fix - Weight validation
* Update - Image lazy loading

= 3.0.2 =
* Update - Delivery library update
* Update - Terminal country selection improvement
* Update - Composer improvements

= 3.0.1 =
* Update - Payment settings menu refactor
* Update - Strict types
* Update - Code style updates

= 3.0.0 =
* Update - New admin section
* Update - Min requirements raised to PHP 7.1
* Update - Delivery service addition

= 2.6.8 =
* Update - Notice box addition

= 2.6.7 =
* Fix - Composer fix

= 2.6.6 =
* Update - Code style updates
* Update - Security improvements
* Update - Composer implementation

= 2.6.5 =
* Fix - Link fix, version update

= 2.6.4 =
* Fix - Settings link fix

= 2.6.3 =
* Fix - Translations fix
* Update - Woocommerce versions update

= 2.6.2 =
* Fix - Bug fix

= 2.6.1 =
* Update - Ownership code, quality sign functionality

= 2.6.0 =
* Fix - Translations fix

= 2.5.9 =
* Fix - Payment display fix
* Fix - Incorrect error logging
* Update - Documentation link change
* Update - Change of the method to reduce stock level
* Update - Add additional payment parameter usage
* Update - Translations

= 2.5.8 =
* Update - WebToPay library

= 2.5.7 =
* Update - Readme information update

= 2.5.6 =
* Update - Readme information update

= 2.5.5 =
* Fix - Mistype in readme
* Fix - Incorrect display when order is created by admin

= 2.5.4 =
* Update - Changed variables naming
* Update - Code improvements

= 2.5.3 =
* Update - WebToPay library
* Update - Default order status establish
* Fix - Project ID input validator
* Fix - Estonian language

= 2.5.2 =
* Update - URL special chars decode

= 2.5.1 =
* Fix - Incorrect stylesheet file url
* Update - Change order of plugin links
* Update - Active payment method border color change

= 2.5.0 =
* Fix - Compatibility with older PHP versions
* Fix - File accessibility

= 2.4.9 =
* Fix - Plugin settings not displaying in new woocommerce
* Update - Plugin links
* Update - Callback logic improvement
* Update - Add plugin description
* Update - Improved plugin init
* Update - Added Admin error text

= 2.4.8 =
* Update - Readme information update
* Update - Links update

= 2.4.7 =
* Update - Readme information update

= 2.4.6 =
* Fix - Multilanguage fix
* Update - Languages: LT, LV, RU, PL, ES
* Fix - Admin textfield fix

Upgrade Notice
--------------
= 3.5.7 =
Fix error on Classic Checkout page

= 3.5.6 =
Update - Prefer shipping phone number over billing phone number for delivery orders
Fix - Fix "lodash" error with new Woocommerce version started from 9.2.0
Improvement - Message about changing the terminal`s location was added to the info log
Fix - Inappropriate shipping gateway is hidden on cart and checkout pages even when 'Hide shipping methods' setting is Disabled
Fix - Changing of the phone number is logged while it was not changed
Fix - min/max weight validations are not working on new checkout page

= 3.5.5 =
Fix - Fix errors in Delivery plugin 
Improvement - Add validation errors text into callback logs

= 3.5.4 =
Fix - Order house number field gets added from session when field is disabled
Fix - Logo alignment near payment method title
Fix - "Undefined country code IS02" error at checkout
Fix - Fix the List of payment methods visibility
Fix - Fix of the Delivery notes in WooCommerce order notes
Improvement - Optimized Paysera plugin queries speed.
Improvement - Optimized plugin CSS, prevented it affect the shipping methods and options

= 3.5.3 =
Fix - Errors with PHP 8.2 version 
Fix - Shipping logo displayed incorrect in grid view (Mobile)
Improve CSS selectors: remove all "!important" tags
New - Add Enable/Disable button for Delivery settings
Remove JS and CSS enqueued files from other unnecessary pages
Added form validations in the Shipping gateway settings window in WooCommerce shipping settings for Paysera's delivery gateways
Fix - Delivery gateway options received default values after migrating to versions 3.5.*
Fix - Order requires a shipping option error for WooCommerce 8.8 versions
Fix - Shipping method configuration values are taken from latest shipping method instead of selected method for same courier company
Added synchronisation between WC orders and Delivery API 
Fix - After updating, plugin becomes deactivated and it's delivery settings are reset to default values

= 3.5.2 =
Added logging functionality for payment and delivery, Delivery order gets created in checkout page instead of thank you page

= 3.5.1 =
Improved delivery order creation process

= 3.5.0 =
Fixed Payment method list would be visible for Quipu, Fixed Selected payment method reset issue after changing payment country, Fixed city selection triggered by different spellings, Paysera Payment and Delivery support added for Block based checkout, Fixed compatibility issue with WooCommerce 8.5.1, Fixed duplicate delivery order issue on some edge cases

= 3.4.3 =
Pass extra information to payment request

= 3.4.2 =
Update WordPress version compatibility with 6.4.2, added Itella, TNT delivery gateway with courier methods only

= 3.4.1 =
Shipping fee is excluded when checking for available payment methods

= 3.4.0 =

Updated WordPress tested upto 6.3, updated Woocommerce tested upto 8.0, fixed payment method list for Quipu, Fixed deactivating plugin while Woocommerce is not activated, fixed strict composer PHP version policy for our plugin, don't load Paysera plugin if Woocommerce is not activated, show a Woocommerce plugin dependency notice if plugin is not activated

= 3.3.5 =

Fixed delivery terminal cities not being loaded after country selection.

= 3.3.4 =

Updated minimum PHP version updated to 7.4, fixed security vulnerabilities for plugin, fixed Paysera delivery terminal selections getting cleared on checkout page due to refresh

= 3.3.3 =

Guzzle library incompatibility fixed

= 3.3.2 =

Fixed Payment plugin get enabled without Project ID.

= 3.3.1 =

Updated Webtopay library supported upto PHP 8.1, Fixed deprecation warnings for PHP 8.1.

= 3.3.0 =

Added plugin information on payment request.

= 3.2.9 =

Added hint for product dimensions if paysera shipping methods are enabled.

= 3.2.8 =

Fixed warning about cache helper file import.

= 3.2.7 =

WordPress tested upto 6.2, fixed Paysera delivery gateways displayed if plugin is not active or gateway is disabled, fixed checkout page default country selection logic, Project ID negative value prevention, Invalid project credential validation on delivery settings.

= 3.2.6 =

Plugin deactivation logic fix, min requirements raised to PHP 7.2.

= 3.2.5 =

Empty product width, weight, height, length error fix in php >= 8.0, fixed terminal selection visibility bug, fixed bug of selected countries not displaying in list of payment methods, fixed critical error with class autoload, introduced compatibility with WooCommerce High-Performance Order Storage, select2 js and css optimization.

= 3.2.4 =

Min/Max weight error fix, updated the validation of the Paysera project fields, fixed bugs in mathematical operations with incorrect data types.

= 3.2.3 =

House field addition to order view in admin.

= 3.2.2 =

Callback URL generating fix.

= 3.2.1 =

Company logo update.

= 3.2.0 =

General improvements, code style fixes, hide shipping methods functionality, delivery methods error rework, payment list enabled by default.

= 3.1.9 =

Delivery terminal and payment method selection fixes, payment methods css fix, plugin description improvements.

= 3.1.8 =

Product weight and dimensions calculation improvements.

= 3.1.7 =

Notice dismiss button fix, unsupported operand types fix, foreach usage fix, auto select delivery terminal country and city.

= 3.1.6 =

Eshop order id addition to delivery order, delivery order receiver address updates for terminal delivery methods, notice dismiss button rework, delivery methods grid view option, prefixing namespaces to avoid conflicts.

= 3.1.5 =

Payment methods grid display fix, terminal country selection improvement, legal name addition to delivery order, delivery calls not made if delivery is not enabled.

= 3.1.4 =

Additional error logging, lang parameter fix.

= 3.1.3 =

Old settings load fix, composer requirements cleanup, additional order notes.

= 3.1.2 =

Payment logo fix, composer improvement, additional check for duplicate plugins, settings backwards compatibility.

= 3.1.1 =

Payment methods style improvements, delivery dimensions fix.

= 3.1.0 =

Plugin name fix.

= 3.0.9 =

Composer file update.

= 3.0.8 =

Payment enable/disable functionality improvement.

= 3.0.7 =

Checkout logo size fix.

= 3.0.6 =

Order creation error fix, terminal fields improvement, order notes improvements.

= 3.0.5 =

Delivery validation error fix, payment settings functionality improvements.

= 3.0.4 =

Hooks and naming update.

= 3.0.3 =

Translations refactor, fixed weight validation, image lazy loading.

= 3.0.2 =

Delivery library update, terminal country selection improvement, composer improvements.

= 3.0.1 =

Payment settings menu refactor, strict types, code style updates.

= 3.0.0 =

New admin section, min requirements raised to PHP 7.1, delivery service addition.

= 2.6.8 =

Notice box addition.

= 2.6.7 =

Composer fix.

= 2.6.6 =

Code style updates, security improvements, composer implementation.

= 2.6.5 =

Link fix, version update.

= 2.6.4 =

Settings link fix.

= 2.6.3 =

Translations fix, woocommerce versions update.

= 2.6.2 =

Bug fix.

= 2.6.1 =

Ownership code, quality sign functionality.

= 2.6.0 =

Translations fix.

= 2.5.9 =

Bug fix and improvements.

= 2.5.8 =

Updated WebToPay library to the latest.

= 2.5.7 =

Updated readme information.

= 2.5.6 =

Updated readme information.

= 2.5.5 =

Minor updates and bug fixes.

= 2.5.4 =

Minor updates.

= 2.5.3 =

Minor bugs fixes and improvements.

= 2.5.2 =

URL special chars decode.

= 2.5.1 =

URL fixes and minor updates.

= 2.5.0 =

Codes fixes. Recommended to install.

= 2.4.9 =

Plugin settings display error fix and minor updates.

= 2.4.8 =

Readme information and links update.

= 2.4.7 =

Readme information update.

= 2.4.6 =

Multilanguage implementation and other fix.

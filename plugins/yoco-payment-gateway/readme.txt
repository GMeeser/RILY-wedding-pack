=== Yoco Payments ===
Contributors: Yoco
Tags: woocommerce,payment gateway
Requires at least: 4.6
Tested up to: 6.1
Requires PHP: 5.6
Stable tag: 2.0.12
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The Yoco Payments plugin lets you easily accept payments via Yoco Payment Gateway on your WooCommerce WordPress site.

== Minimum Requirements ==
- WordPress 4.6
- WooCommerce 2.6.0 or greater
- PHP version 5.6.20 or greater. PHP 7.2 or greater is recommended
- MySQL version 5.0 or greater. MySQL 5.6 or greater is recommended

== Description ==
Accept payments on your WooCommerce online store with Yoco.

The Yoco plugin for WooCommerce gives your customers an on-site payment experience, optimising conversion as your customers are never redirected to a different page to make the payment. Payments are processed through the Yoco platform, so you can see all your online and in-store sales in one place.

= WHY USE YOCO'S PAYMENT GATEWAY? =

* No upfront, monthly or payout fees.
* Card transaction fees start at 2.95% ex. VAT. and EFT from 2% ex. VAT.
* Daily payouts.
* Sign up and accept payments within minutes.
* On-site integrated payments.
* 3D Secure and PCI compliant.
* Online and in-store sales in one place.


= SIGN UP AND START ACCEPTING PAYMENTS IN MINUTES =
Setup is really easy, takes less than 10 minutes, and is completely free.

1. [Sign up for a Yoco account](https://signup.yoco.co.za/za/signup/v1/), if you're not already registered.
2. Install the Plugin and retrieve your API keys in just a few clicks. [More detailed instructions](https://support.yoco.help/s/article/Yoco-for-WooCommerce).

That's it! You're ready to accept payments.

= ON-SITE PAYMENT EXPERIENCE =
Once you've integrated the plugin, your customers will see a new Yoco payment method on checkout. They can complete payment directly on your website, increasing conversion and creating a slick, on-brand customer experience.

Yoco currently accepts card payments via Visa and Mastercard. The payment gateway is PCI compliant and supports 3D Secure.
EFT payments are accepted from Absa, Capitec, FNB, Nedbank and Standard Bank.
All payments are made in South African Rands (ZAR).

= SIMPLE TRANSACTION FEES =
With Yoco, there are no upfront, monthly or payout fees. We have a simple transaction fee, 2% excl VAT. for EFT and 2.95% excl VAT. for card payments.

[More information here](https://www.yoco.co.za/za/transaction-fees/).

= ALL YOUR SALES IN ONE PLACE =
The transaction info is captured in the Orders menu, and you can view all payments in your WordPress admin dashboard to stay on top of everything.

In addition, you can see all your online and in-store payments in one place in your Yoco Business Portal. You also benefit from Yoco business tools, and access to working capital.

ABOUT YOCO
Yoco is an African technology company that builds tools and services to help small businesses get paid, run their business better and grow. We believe that by opening up more possibilities for entrepreneurs to be successful, we can help create more jobs, enable people to thrive and help to drive our economy forward.

== Screenshots ==
1.
2.

== Installation ==
Please note, the plugin requires an SSL encrypted site, PHP version 5.6.20 or greater (although PHP 7.2 or greater is recommended) and ideally the latest WooCommerce and WordPress versions.

**AUTOMATIC INSTALLATION**
Automatic installation is the easiest option. WordPress handles the file transfers and you don't even need to leave your web browser.

To do an automatic install of the Yoco payment plugin:
* Log in to your WordPress dashboard, navigate to the Plugins menu and click 'Add New'.
* Type 'Yoco Payment Gateway' into the search bar and click 'Search Plugins'.
* Once you've found our plugin, install it by clicking 'Install Now' followed by 'Activate'.
* Once you've installed the plugin, you can fetch your API keys from the 'Sell Online > Payment Gateway' menu in your [Yoco Business Portal](https://portal.yoco.co.za/online/plugin-keys).
* Test with your test keys and a [test card](https://developer.yoco.com/online/resources/testing-info/#test-cards), before switching to the live keys for real transactions.
* More detailed installation notes can be found [here.](https://support.yoco.help/s/article/Yoco-for-WooCommerce)

**UPDATING**

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==
= What currencies does this plugin support? =
Yoco currently accepts payments via EFT, Visa and Mastercard. All payments are made in South African Rands (ZAR).

= How do I test the plugin? =
Set the plugin to Test mode, fetch your test API keys from the [Yoco Business Portal](https://portal.yoco.co.za/online/plugin-keys) and add these to the plugin configuration. Now try a test payment using one of the [test cards](https://developer.yoco.com/online/resources/testing-info/#test-cards). Note that test transactions won't appear in the Yoco Business Portal. Using a real credit card in Test mode will also fail. Once you're ready, set the plugin to Live mode and switch to the live API keys for real transactions!

= Where do I find documentation or support? =
More detailed installation notes can be found [here](https://support.yoco.help/s/article/Yoco-for-WooCommerce). You can email [ecommerce@yoco.com](mailto:ecommerce@yoco.com) or connect with us through our online community [https://community.yoco.com/](https://community.yoco.com/).

== Changelog ==

= 2.0.12 =
* Added support for PHP version 8

= 2.0.11 =
* Update EFT pricing

= 2.0.10 =
* Security updates

= 2.0.9 =
* Fix notice message

= 2.0.8 =
* Miscellaneous fixes and updates

= 2.0.7 =
* Fix updates to admin settings for firms with invalid keys

= 2.0.6 =
* Miscellaneous fixes and updates

= 2.0.5 =
* Updates to admin settings

= 2.0.4 =
* Updates to admin settings

= 2.0.3 =
* Differentiate card and EFT status

= 2.0.2 =
* Added EFT as a payment option

= 2.0.1 =
* Reverted to the previous name for the plugin's main file. If you have already upgraded to v2.0.0, upgrading to v2.0.1 will mean you’ll need to manually activate the plugin again. We encourage you to do this, as any upgrade from v2.0.0 will need this.

= 2.0.0 =
* Customer can save card for later use
* Integration of Yoco’s new payment APIs

= 1.53 =
* Added SVG checkout logo

= 1.52 =
* Updated checkout logo
* Improved error handling

= 1.51 =
* Improved retries for slow network conditions
* Updated branding

= 1.50 =
* Support legacy PHP 7 versions

= 1.49 =
* Better error recovery and retries
* Fix for misleading SQL error in logs
* Updated guidance and contact details

= 1.48 =
* Update to meet WP.org compliance review

= 1.47 =
* Handle transient connection errors with multiple retries
* More reliable error logging and reporting
* WordPress 5.6 test declaration

= 1.46 =
* Add WooCommerce version check support to plugin header
* Ensure order total is always consistent
* Add filter wc_yoco_popup_configuration

= 1.45 =
* Better error handling
* More useful error messages displayed to merchant
* Ensure Order status is updated correctly

= 1.44 =
* Bugfixes

= 1.43 =
* Fixed Virtual Product AutoComplete Bug

= 1.41 =
* Auto Complete Virtual Orders Variations Bugfix

= 1.40 =
* An improved payment experience that is simpler and quicker. This is the first of several improvements we will be releasing.
* Clearer error responses to give merchants better insight into failed transactions
* Automated order completion, on successful payment, for virtual or digital product orders

= 1.030 =
* Improved client error logging and Yoco client diagnostics
* Site in sub-folder fix

= 1.021 =
* Improved client error logging and Yoco client diagnostics
* Edge case rounding issue fix
* WooCommerce Notice on plugin admin page if trying to activate and WooCommerce is not active/installed

= 1.010 =
* Replaced Guzzle with Wordpress native functions
* Improved client error logging and Yoco client diagnostics
* Updated Plugin Readme.md

= 1.000 =
* Initial Release.

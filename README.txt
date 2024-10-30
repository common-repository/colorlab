=== Printlane™ Product Designer ===
Tags: product designer, product customizer, web to print, product configurator, product editor, web 2 print
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 1.5.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce integration of Printlane™ Interactive Product Designer

== Description ==

[Printlane™](https://printlane.com) is an online product personalization platform that helps you to accelerate your web to print workflow.

This plugin integrates the Printlane™ Product Designer into your WooCommerce store and allows you to receive customer designs for products in your e-commerce store.

You need to have a Printlane™ account to enable the designer and connect it to your product templates in Printlane™ Studio. You can request one [here](https://printlane.com/demo).

If you need help with integrating Printlane™ in your Wordpress, please take a look at the [Printlane™ Help Center](https://help.printlane.com/integrations/wordpress.html) or [get in touch with Support](mailto:support+wordpress@printlane.com).

== Installation ==

1. Upload `woocommerce-printlane` to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in your WordPress Admin
3. Enter your Printlane `Store ID` in the menu WooCommerce -> Settings -> Printlane Tab

Please visit the [Printlane™ Help Center](https://help.printlane.com/integrations/wordpress.html) for more information.

== Technical information ==

This plugin automates the integration of the Printlane™ Product Designer in your WooCommerce store, and is an easy way to do the [javascript integration](https://help.printlane.com/integrations/javascript.html) without writing any code.

A javascript script will be added to your store pages which load the app, and replaces the _Add to cart_ button with a _Personalize_ button for products where you have enabled Personalization.

== Support ==

We offer free [technical support](mailto:support@printlane.com) in English.

== Changelog ==
= 1.5.5 =
* Improve implementation for product bundles by supporting bundles that contain variable products

= 1.5.4 =
* Add support for woocommerce product bundles

= 1.5.3 =
* Select a custom add to cart button by configuring a unique CSS selector

= 1.5.2 =
* Fix bug, Printlane Designer unexpectedly bound to add to cart button

= 1.5.1 =
* Fix bug, Printlane Designer unexpectedly bound to add to cart button

= 1.5.0 =
* Add support for woocommerce checkout blocks
* Tested up to wordpress

= 1.4.2 =
* Fix `open` and `download` links for the design appearing in order confirmation pages and emails
* Tested up to Wordpress 6.4.3

= 1.4.1 =
* Admin: use `Printlane Design` as key and show the Design ID together with an `Open` and `Download` link
* Tested up to Wordpress 6.4.2

= 1.4.0 =
* Update to connect with Printlane™ endpoints

= 1.3.7 =
* Remove deprecated hook woocommerce_add_order_item_meta

= 1.3.5 =
* Add unique class to add to cart button if product variant is customizable

= 1.3.4 =
* Fix bug, template ID field on product variant level cannot be emptied
* Add setting to hide reference on order detail pages and emails
* Add a Template ID field for variable products
* Fix bug, button icons disappear when switching between product variations on the product page

= 1.3.3 =
* update Order URL endpoint integration to match latest version
* test compatibility with Wordpress 6.0.2

= 1.3.2 =
* Support activating and deactivating on product variant level

= 1.3.1 =
* make string "Reference" translatable in pot file

= 1.3.0 =
* Test compatibility with Wordpress 6
* make text "Reference" translatable

= 1.2.0 =
* Update integration of settings page to be compatible with other plugins
* Test plugin for Wordpress version 5.9.3, WooCommerce 6.4.1 and PHP 8.
* Update implementation for getting order id's to use `get_order_number` to be compatible with plugins overriding the order number

= 1.1.0 =
* Test plugin for Wordpress version 5.6.0.
* Fix an issue where the app couldn't open variants of a product when the variants were translated using WPML.

= 1.0.10 =
* Test plugin for Wordpress version 5.5.1.

= 1.0.9 =
* Updated the app to the latest version, including support for SVG, artwork categories and many more features!

= 1.0.8 =
* Code improvements

= 1.0.7 =
* We’ve added support for the Order Api, this gives WordPress customers the option to push orders to the backend for fulfilment.

= 1.0.6 =
* We've added support for showing thumbnails of a customization in the cart

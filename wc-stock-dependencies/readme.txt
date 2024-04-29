=== Stock Dependencies for WooCommerce ===
Contributors: KevinMcCall
Tags: woocommerce,product,inventory,group,dependency
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.6.2
Requires PHP: 7.0
License: MIT
License URI: https://github.com/kmac420/stock-dependencies-for-woocommerce/blob/master/LICENSE

Make your products' availability and stock dependent on the inventory of other
products in your WooCommerce store.

== Description ==

With Stock Dependencies for WooCommerce, you can make the products and
variations in your WooCommerce store dependent on the inventory of your other
products or variations. Customers will be able to select and purchase the
product without seeing the products on which it depends in their cart, during
their checkout, or on their receipt. Inventory management in Woo Commerce is
greatly simplified since you only have to manage inventory levels for the
item(s) on which your product or variation is dependent.

Stock Dependencies for WooCommerce works for Simple and Variable product types
in WooCommerce and you can make a product or variation dependent on a
combination of other products and variations. Stock Dependencies for WooCommerce
lets you create dependencies on quantities of one or more of the other products.

Stock Dependencies for WooCommerce is ideal for:

* Selling products in multiple quantities. For an product you already have in
your inventory, you can use Stock Dependencies for WooCommerce to sell, for
example, a package of six items and and package of 12 items. With Stock
Dependencies for WooCommerce you do not need to maintain inventory levels for
each quantity of the product as the product inventory is managed for only the
single quantity item.
* Selling bundled products. You can create a bundle of
multiple items and sell them as a single item. With Stock Dependencies for
WooCommerce your customers will only see the bundle product in their cart,
during the checkout process, and on their order receipt.

When a product with stock dependencies is displayed in your store, Stock
Dependencies for WooCommerce will check the inventory of the products on which
it depends and will only show the product as being available if all the
dependent stock items are available. When a product with stock dependencies is
added to a shopping cart and eventually purchased, the customer will only see
the single product in their cart and order, and will not see the products on
which it is dependent. When the product is purchased, Stock Dependencies for
WooCommerce will reduce the inventory of the items on which it is dependent by
the appropriate amount.

## Configuring

Stock Dependencies for WooCommerce is easy to configure for any simple or
variable product in your WooCommerce store. A single checkbox is added to each
simple product or variation in your WordPress admin that allows you to enable
stock dependencies for that product or variation. Once checked, two fields are
added for the SKU and the quantity of the dependency. Additional dependencies
can be easily added.

## Shopping

When a customer views a product with dependencies in your WooCommerce store,
they will see the product as you have configured it, but the available quantity
and in-stock status will be determined by Stock Dependencies for WooCommerce
from the available quantities of each of the products and variations on which it
is dependent.

## Cart, Checkout, and Receipt

Customers will only see the product they selected, and not the products upon
which it is dependent, in their shopping cart, during the checkout process, and
on their receipt.

## Restocking Refunds and Cancelled Orders

When you issue a refund or cancel an order that has stock dependencies, the
plugin will restock the dependency products.

== Installation ==

Install the WooCommerce Stock Dependencies plugin

1. Download and install the plugin from the [release
page](https://github.com/kmac420/stock-dependencies-for-woocommerce/releases) or
install directly from the [WordPress plugin
directory](https://wordpress.org/plugins/wc-stock-dependencies).
1. Activate the plugin.
1. In your WordPress admin, navigate to the Products listing page and select a
product to configure.
1. Enable stock dependencies.
1. For simple products, edit the product and navigate to the Inventory tab and
check the "Add stock dependency" checkbox. Add the SKU and the quantity for each
product or variation on which this product is dependent.
1. For variable products, edit the product and navigate to the Variations tab
and check the "Add stock dependency" checkbox for each variation. Add the SKU
and the quantity for each product or variation on which this product variation
is dependent.

== Frequently Asked Questions ==

= Why would I use Stock Dependencies for WooCommerce? =

Stock Dependencies for WooCommerce is ideal for selling groups of one or more
products without having to maintain inventory numbers for each grouping and
without having each product in the grouping appear as a separate line item in
the order and on the receipt.

= How is Stock Dependencies for WooCommerce different from other product grouping plugins? =

Other product grouping plugins allow you to create different combinations of
products within your store but still treat each as a separate product in the
customer's shopping cart, during the checkout process, and on the customer's
order receipt.

= How are dependencies defined? =

Each dependency that a product or variation has on another product or variation
is defined using the (unique) SKU of the product and the quantity of that
product that is required.

= Can stock dependencies be created with Product IDs or other identifiers? =

No. Stock dependencies can only be created with a unique SKU of the product or
variation on which the dependency exists.

== Screenshots ==

1. When editing a simple or variable product, easily add Stock Dependencies.
2. The quantity of items available is calculated by the plugin based on the dependencies' quantities.
3. The inventory of each product dependency is automatically reduced by the appropriate number based on the order quantity and the dependency quantity.

== Changelog ==

= 1.6.2 =
* Adds an admin tool to check dependencies

= 1.6.1 =
* Verified with Wordpress 6.5
* Verified with WooCommerce 8.7
* Added a plugin tools page and a feature to clear Stock Dependency database transients

= 1.6 = * Confirmed compatibility with WooCommerce High Performance Order Tables
* Confirmed compatibility with WordPress 6.4
* Plugin will now update the inventory quantity in admin to match the quantity
determined by the stock depedencies after each purchase, refund, or order
cancellation. Note that this only affects the behaviour of the plugin in admin
as the actual inventory is calculated every time a product page is loaded in the
shop

= 1.5 =
* Stores product IDs for stock dependencies in WordPress transients to speed up lookups
* Cancelling an order restocks dependencies

= 1.4 =
* Enforces SKU validity during dependency configuration
* Reduces the need to lookup product IDs from SKUs by storing the ID in the dependency metadata

= 1.3.1 =
* Verified with Wordpress 5.7
* Verified with WooCommerce 5.3

= 1.3 =
* Verified with Wordpress 5.6
* Verified with WooCommerce 4.9
* Display stock dependencies on order line items in admin
* Support for allowing backorders

= 1.2.1 =
* Verified with WooCommerce 4.4

= 1.2 =
* Stock dependencies are restocked during a refund

= 1.1.2 =
* Tested with WordPress 5.5 and WooCommerce 4.3

= 1.1.1 =
* Fixes an issue where orders created in WooCommerce admin have their stock depedencies reduced twice

= 1.1 =
* Adds support for stock dependencies in orders created from within WooCommerce admin
* Fixes an issue where product inventory status was not displayed correctly in WooCommerce admin

= 1.0 =
* Initial release of Stock Dependencies for WooCommerce

== Upgrade Notice ==

= 1.5 =
* Performance improvements
* Cancelling an order restocks dependencies
* Verified compatibility with WordPress 5.9 and WooCommerce 6.4

= 1.4 =
* Reduces frequency of certain plugin queries

= 1.3 =
* Verified compatibility with WordPress 5.6 and WooCommerce 4.9 and added some minor features

= 1.2.1 =
* Plugin verified with WooCommerce 4.4

= 1.2 =
* Upgrade to get the latest feature and have your stock dependencies restocked during a refund

= 1.1.2 =
* Tested with WordPress 5.5 and WooCommerce 4.3

= 1.1.1 =
* Bug fixes

= 1.1 =
* This update adds support for orders created in admin and fixes an issue
with order status displayed in admin

= 1.0 =
* This is the initial release of the Stock Dependencies for WooCommerce plugin.

== Privacy ==

The Stock Dependencies for WooCommerce plugin does not affect the way personal
information is collected or stored within your WooCommerce store or your
WordPress installation.

== Open Source ==

The Stock Dependencies for WooCommerce plugin is open source software. Feel free
to contribute or fork this code on
[GitHub](https://github.com/kmac420/stock-dependencies-for-woocommerce/).

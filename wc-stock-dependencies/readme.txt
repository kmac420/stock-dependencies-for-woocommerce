=== WC Stock Dependencies ===
Contributors: kevinmccall
Tags: woocommerce,stock,dependencies,product,simple,variation,inventory,dependency
Requires at least: 5.0
Tested up to: 5.4
Stable tag: 1.0
Requires PHP: 7.0
License: MIT
License URI: https://github.com/kmac420/wc-stock-dependencies/blob/master/LICENSE

With WC Stock Dependencies, you can make the products and variations in your WooCommerce store dependent on the inventory of your other products or variations. Customers will be able to select and purchase the product without seeing the products on which it depends in their cart, during their checkout, or on their receipt. WC Stock Dependencies is ideal for selling products in multiple quantities -- for example, a single item, a package of six items, and and package of 12 items -- with the product inventory being maintained only for the single quantity item.

== Description ==

With WC Stock Dependencies, you can make the products and variations in your WooCommerce store dependent on the inventory of your other products or variations. Customers will be able to select and purchase the product without seeing the products on which it depends in their cart, during their checkout, or on their receipt. Inventory management in Woo Commerce is greatly simplified since you only have to manage inventory levels for the item(s) on which your product or variation is dependent.

WC Stock Dependencies works for Simple and Variable product types in WooCommerce and you can make a product or variation dependent on a combination of other products and variations. WC Stock Dependencies lets you create dependencies on quantities of one or more of the other products.

WC Stock Dependencies is ideal for:
* Selling products in multiple quantities. For an product you already have in your inventory, you can use WC Stock Dependencies to sell, for example, a package of six items and and package of 12 items. With WC Stock Dependencies you do not need to maintain inventory levels for each quantity of the product as the product inventory is managed for only the single quantity item.
* Selling bundled products. You can create a bundle of multiple items and sell them as a single item. With WC Stock Dependencies your customers will only see the bundle product in their cart, during the checkout process, and on their order receipt.

When a product with stock dependencies is displayed in your store, WC Stock Dependencies will check the inventory of the products on which it depends and will only show the product as being available if all the dependent stock items are available. When a product with stock dependencies is added to a shopping cart and eventually purchased, the customer will only see the single product in their cart and order, and will not see the products on which it is dependent. When the product is purchased, WC Stock Dependencies will reduce the inventory of the items on which it is dependent by the appropriate amount.

## Configuring

WC Stock Dependencies is easy to configure for any simple or variable product in your WooCommerce store. A single checkbox is added to each simple product or variation in your WordPress admin that allows you to enable stock dependencies for that product or variation. Once checked, two fields are added for the SKU and the quantity of the dependency. Additional dependencies can be easily added.

## Shopping

When a customer views a product with dependencies in your WooCommerce store, they will see the product as you have configured it, but the available quantity and in-stock status will be determined by WC Stock Dependencies from the available quantities of each of the products and variations on which it is dependent.
   
## Cart, Checkout, and Receipt

Customers will only see the product they selected, and not the products upon which it is dependent, in their shopping cart, during the checkout process, and on their receipt.

== Installation ==

Install the WooCommerce Stock Dependencies plugin

1.  Download and install the plugin from the [release page](https://github.com/kmac420/wc-stock-dependencies/releases) or install directly from the WordPress plugin directory.
1.  Activate the plugin.
1.  In your WordPress admin, navigate to the Products listing page and select a product to configure.
1.  Enable stock dependencies.
1.  For simple products, edit the product and navigate to the Inventory tab and check the "Add stock dependency" checkbox.
1.1.  Add the SKU and the quantity for each product or variation on which this product is dependent.
1.  For variable products, edit the product and navigate to the Variations tab and check the "Add stock dependency" checkbox for each variation.
1.1.  Add the SKU and the quantity for each product or variation on which this product variation is dependent.

== Frequently Asked Questions ==

= Why would I use Stock Dependencies for WooCommerce? =

WC Stock Dependencies is ideal for selling groups of one or more products without having to maintain inventory numbers for each grouping and without having each product in the grouping appear as a separate line item in the order and on the receipt.

= How is WC Stock Dependencies different from other product grouping plugins? =

Other product grouping plugins allow you to create different combinations of products within your store but still treat each as a separate product in the customer's shopping cart, during the checkout process, and on the customer's order receipt.

= How are dependencies defined? =

Each dependency that a product or variation has on another product or variation is defined using the (unique) SKU of the product and the quantity of that product that is required.

= Can stock dependencies be created with Product IDs or other identifiers? =

No. Stock dependencies can only be created with a unique SKU of the product or variation on which the dependency exists.

== Screenshots ==

1. When editing a simple or variable product, easily add Stock Dependencies.
2. The quantity of items available is calculated by the plugin based on the dependencies' quantities.
3. The inventory of each product dependency is automatically reduced by the appropriate number based on the order quantity and the dependency quantity.

== Changelog ==

= 1.0 =
* Initial release of WC Stock Dependencies

== Upgrade Notice ==

= 1.0 =
This is the initial release of the WC Stock Dependencies plugin.

== Privacy ==

The WC Stock Dependencies plugin does not affect the way personal information is collected or stored within your WooCommerce store.

== Open Source ==

The WC Stock Dependencies plugin is open source software. Feel free to contribute or fork this code on [GitHub](https://github.com/kmac420/wc-stock-dependencies/).

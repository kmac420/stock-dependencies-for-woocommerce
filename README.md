# WC Stock Dependencies Plugin

## Overview

With WC Stock Dependencies, you can make the products and variations in your WooCommerce store dependent on the inventory of your other products or variations. Customers will be able to select and purchase the product without seeing the products on which it depends in their cart, during their checkout, or on their receipt. Inventory management in Woo Commerce is greatly simplified since you only have to manage inventory levels for the item(s) on which your product or variation is dependent.

WC Stock Dependencies works for Simple and Variable product types in WooCommerce and you can make a product or variation dependent on a combination of other products and variations. WC Stock Dependencies lets you create dependencies on quantities of one or more of the other products.

WC Stock Dependencies is ideal for:

- **Selling products in multiple quantities**. For an product you already have in your inventory, you can use WC Stock Dependencies to sell, for example, a package of six items and and package of 12 items. With WC Stock Dependencies you do not need to maintain inventory levels for each quantity of the product as the product inventory is managed for only the single quantity item.
- **Selling bundled products**. You can create a bundle of multiple items and sell them as a single item. With WC Stock Dependencies your customers will only see the bundle product in their cart, during the checkout process, and on their order receipt. You do not need to manage the inventory of the bundled item as it's availability is determined by the availability of the dependencies.

When a product with stock dependencies is displayed in your store, WC Stock Dependencies will check the inventory of the products on which it depends and will only show the product as being available if all the dependent stock items are available. When a product with stock dependencies is added to a shopping cart and eventually purchased, the customer will only see the single product in their cart and order, and will not see the products on which it is dependent. When the product is purchased, WC Stock Dependencies will reduce the inventory of the items on which it is dependent by the appropriate amount.

## How it works

### Product management

WC Stock Dependencies is easy to configure for any simple or variable product in your WooCommerce store. A single checkbox is added to each simple product or variation in your WordPress admin that allows you to enable stock dependencies for that product or variation. Once checked, two fields are added for the SKU and the quantity of the dependency. Additional dependencies can be easily added.

For example, if you are selling individual bottles of wine in your WooCommerce store, but also want to sell wine in cases of 12 bottles, you can create a product with two variations:

1. Quantity: Single bottle

   This variation will have inventory managment enabled, will have a unique SKU, and you will enter the price and total number of bottles as the available stock.

1. Quantity: Care of 12 bottles

   You will configure this variation with a unique SKU and a price. This variation will have stock dependencies enabled and will have a dependency on the single bottle variation with a dependency quantity of 12. Once the product variation is configured with a stock dependency, you never have to manage its inventory.

### Shopping

When a customer views a product with dependencies in your WooCommerce store, they will see the product as you have configured it, but the available quantity and in-stock status will be determined by the WC Stock Dependencies plugin from the available quantities of each of the products and variations on which it is dependent.

In our example from above, the customer views the product page for the wine and will select a variation of either a single bottle or 12-bottle case. Let's say you have 100 bottles of wine in your inventory, the first variation will show a total of 100 bottles available and the second variation - the case of 12 bottles - will show a total of 8 cases available (since you can fill and ship only 8 cases from your 100 bottles).

As purchases are made, only the inventory quantity for the single bottle of wine is changed.

### Cart, checkout, and receipt

Customers will only see the product they selected, and not the products upon which it is dependent, in their shopping cart, during the checkout process, and on their purchase receipt. When a purchase is made, the WC Stock Dependencies plugin will automatically reduce the inventory of the dependencies by the quantity of the order times the quantity of the dependency.

In our wine example, the customer will see the case of wine in their shopping cart, during the checkout process, and on their receipt (i.e. they will not see the 12 individual bottles of wine).

## Setup and configuration of WC Stock Dependencies

### Installation

Install the WooCommerce Stock Dependencies plugin

1. Download and install the plugin from the [release page](https://github.com/kmac420/wc-stock-dependencies/releases) or install directly from the [WordPress plugin directory](https://wordpress.org/plugins/wc-stock-dependencies/).
1. Activate the plugin.

### Configuring Stock Dependencies for a simple product

As an example of using WC Stock Dependencies for a simple WooCommerce product, we will use the WooCommerce sample data and create a simple product called _Beanie with Logo 2-pack_ that has a dependency on the sample _Beanie with Logo_ product with a dependency quantity of 2.

1. In your WordPress admin, navigate to the Products listing page.
1. Create a duplicate of the _Beanie with Logo_ product and call it _Beanie with Logo 2-pack_.
1. Give the new product the SKU _Woo-beanie-logo-2-pack_.
1. Save the product.
1. Navigate to the Inventory tab and check the **Add stock dependency** checkbox. ![Simple Product settings](/images/wcsd-simple-add-stock-dependency.png)
   - Note that when you check the **Add stock dependencies** checkbox the **Manage stock?** field will automatically be checked and the **Stock quantity** field will be automatically set to 0 and both fields will be converted to read-only fields.
1. In the newly added stock dependencies fields, add the SKU (Woo-beanie-logo) and the quantity (2) for the product dependency. Note that you cannot add the SKU of the current product and the plugin will prevent you from doing so. ![Adding the SKU and quanty for a stock dependency](/images/wcsd-simple-add-stock-dependency-sku.png)
1. If you want to add an additional stock dependency, click on the **Add stock dependency** link immediately below the existing stock dependency fields. ![Adding and additional stock dependency](/images/wcsd-simple-add-stock-dependency-addional.png)
1. Save your product changes.
1. Navigate back to the Product listing page and you will see that the available inventory on the _Beanie with Logo 2-pack_ is half of the inventory available for the _Beanie with Logo_ product. ![Product listing showing stock dependency](/images/wcsd-simple-add-stock-dependency-product-listing.png)

### Configuring Stock Dependencies for a variable product

As an example of using WC Stock Dependencies for a variable WooCommerce product, we will use the WooCommerce sample data and convert the grouped product called _Logo Collection_ to a variable product where each variation has a dependency on the sample _T-Shirt with Logo_, _Hoodie with Logo_, and _Beanie with Logo_ products. The _T-Shirt with Logo_ and _Hoodie with Logo_ sample products will also be converted to variable products.

1. In your WordPress admin, navigate to the Products listing page.
1. Edit the _Logo Collection_ product and change the product to a variable product.
1. Save the product.
1. Navigate to the **Attributes** tab and select _Size_ from the select menu and click the **Add** button.
1. In the Attribute settings box, add the sizes _Small_, _Medium_, and _Large_ to the list of **Value(s)** and check the **Used for variations** checkbox.
1. Save the product changes before proceeding.
1. Navigate to the **Variations** tab and select _Create variations from all attributes_ from the select menu.
1. Save the variations.
1. After the product data reloads, navigate back to the **Variations** tab and expand all the variations.
1. For each of the three variations (_Small_, _Medium_, and _Large_) we will add the SKU and price, and stock dependencies.
   | Variation | SKU | Price |
   | --- | --- | --- |
   | Large | Logo-collection-L | 85 |
   | Medium | Logo-collection-M | 85 |
   | Small | Logo-collection-S | 85 |
1. For the Large variation settings we will add the stock dependencies.
   - Check the **Add stock dependencies** checkbox. ![Simple Product settings](/images/wcsd-variable-add-stock-dependency.png)
     - Note that when you check the **Add stock dependencies** checkbox the **Manage stock?** field will automatically be checked and the **Stock quantity** field will be automatically set to 0 and both fields will be converted to read-only fields. These settings are required for the WC Stock Dependencies plugin to function correctly.
   - Add the dependency SKU _Woo-hoodie-with-logo-L_ and quantity _1_ for the first stock dependency.
   - Click the **Add stock dependency** link to add another stock dependency row.
   - Add the dependency SKU _Woo-tshirt-logo-L_ and quantity _1_ for the second stock dependency.
   - Click the **Add stock dependency** link to add another stock dependency row.
   - Add the dependency SKU _Woo-beanie-logo_ and quantity for the third stock dependency. ![Adding the SKU and quanty for a stock dependency](/images/wcsd-variable-add-stock-dependency-sku.png)
1. Repeat the same steps for the Medium variation stock dependency settings:
   - Check the **Add stock dependencies** checkbox.
   - Add the dependency SKU _Woo-hoodie-with-logo-M_ and quantity _1_ for the first stock dependency.
   - Click the **Add stock dependency** link to add another stock dependency row.
   - Add the dependency SKU _Woo-tshirt-logo-M_ and quantity _1_ for the second stock dependency.
   - Click the **Add stock dependency** link to add another stock dependency row.
   - Add the dependency SKU _Woo-beanie-logo_ and quantity for the third stock dependency.
1. Repeat the same steps for the Small variation stock dependency settings:
   - Check the **Add stock dependencies** checkbox.
   - Add the dependency SKU _Woo-hoodie-with-logo-S_ and quantity _1_ for the first stock dependency.
   - Click the **Add stock dependency** link to add another stock dependency row.
   - Add the dependency SKU _Woo-tshirt-logo-S_ and quantity _1_ for the second stock dependency.
   - Click the **Add stock dependency** link to add another stock dependency row.
   - Add the dependency SKU _Woo-beanie-logo_ and quantity _1_ for the third stock dependency.
1. Save your variation changes.

## Todo

- [ ] Restock inventory for refunds
- [ ] Check dependency SKU validity during configuration

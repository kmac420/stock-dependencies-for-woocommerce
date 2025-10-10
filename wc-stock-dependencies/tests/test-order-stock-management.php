<?php

use StockDependenciesForWooCommerce\Admin\Admin;

class Test_Order_Stock_Management extends WP_UnitTestCase {
    private $parent_product;
    private $dependent_product;
    private $admin;
    private $order;

    public function setUp(): void {
        parent::setUp();
        
        // Create an admin instance
        $this->admin = new Admin();

        // Create a parent product
        $this->parent_product = new WC_Product_Simple();
        $this->parent_product->set_name('Parent Product');
        $this->parent_product->set_regular_price('10.00');
        $this->parent_product->set_stock_quantity(10);
        $this->parent_product->set_manage_stock(true);
        $this->parent_product->save();

        // Create a dependent product
        $this->dependent_product = new WC_Product_Simple();
        $this->dependent_product->set_name('Dependent Product');
        $this->dependent_product->set_regular_price('20.00');
        $this->dependent_product->set_stock_quantity(5);
        $this->dependent_product->set_manage_stock(true);
        $this->dependent_product->save();

        // Set up dependency: 2 parent items needed for each dependent item
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_id', $this->parent_product->get_id());
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_qty', 2);

        // Create a test order
        $this->order = wc_create_order();
    }

    public function tearDown(): void {
        // Clean up
        $this->parent_product->delete(true);
        $this->dependent_product->delete(true);
        $this->order->delete(true);
        parent::tearDown();
    }

    public function test_reduce_stock_on_order() {
        // Add dependent product to order
        $this->order->add_product($this->dependent_product, 2); // Order 2 units
        $item_id = array_keys($this->order->get_items())[0];

        // Initial stock levels
        $initial_parent_stock = $this->parent_product->get_stock_quantity();
        $initial_dependent_stock = $this->dependent_product->get_stock_quantity();

        // Process the order
        $this->admin->reduce_order_stock($this->order);

        // Refresh product data
        $this->parent_product = wc_get_product($this->parent_product->get_id());
        $this->dependent_product = wc_get_product($this->dependent_product->get_id());

        // Check stock levels
        // 2 dependent items ordered, each needs 2 parent items = 4 parent items reduced
        $this->assertEquals($initial_parent_stock - 4, $this->parent_product->get_stock_quantity());
        $this->assertEquals($initial_dependent_stock - 2, $this->dependent_product->get_stock_quantity());
    }

    public function test_restock_on_cancelled_order() {
        // Add dependent product to order
        $this->order->add_product($this->dependent_product, 2);
        $this->admin->reduce_order_stock($this->order);

        $initial_parent_stock = $this->parent_product->get_stock_quantity();
        $initial_dependent_stock = $this->dependent_product->get_stock_quantity();

        // Cancel the order
        $this->admin->restock_cancelled_order($this->order->get_id());

        // Refresh product data
        $this->parent_product = wc_get_product($this->parent_product->get_id());
        $this->dependent_product = wc_get_product($this->dependent_product->get_id());

        // Check if stocks are restored
        $this->assertEquals($initial_parent_stock + 4, $this->parent_product->get_stock_quantity());
        $this->assertEquals($initial_dependent_stock + 2, $this->dependent_product->get_stock_quantity());
    }

    public function test_restock_refunded_item() {
        // Add dependent product to order
        $this->order->add_product($this->dependent_product, 3);
        $item_id = array_keys($this->order->get_items())[0];
        $this->admin->reduce_order_stock($this->order);

        $initial_parent_stock = $this->parent_product->get_stock_quantity();
        $initial_dependent_stock = $this->dependent_product->get_stock_quantity();

        // Refund 2 items
        $this->admin->restock_refunded_item(
            $item_id,
            2, // Quantity to refund
            $this->dependent_product->get_id(),
            $this->order,
            $this->dependent_product
        );

        // Refresh product data
        $this->parent_product = wc_get_product($this->parent_product->get_id());
        $this->dependent_product = wc_get_product($this->dependent_product->get_id());

        // Check if stocks are restored correctly
        // 2 items refunded, each had 2 parent items = 4 parent items restored
        $this->assertEquals($initial_parent_stock + 4, $this->parent_product->get_stock_quantity());
        $this->assertEquals($initial_dependent_stock + 2, $this->dependent_product->get_stock_quantity());
    }

    public function test_order_item_meta() {
        // Add dependent product to order
        $this->order->add_product($this->dependent_product, 2);
        $item_id = array_keys($this->order->get_items())[0];

        // Process the order
        $this->admin->reduce_order_stock($this->order);

        // Check if dependency information is stored in order item meta
        $parent_id = wc_get_order_item_meta($item_id, '_wcsd_parent_id', true);
        $parent_qty = wc_get_order_item_meta($item_id, '_wcsd_parent_qty', true);

        $this->assertEquals($this->parent_product->get_id(), $parent_id);
        $this->assertEquals(2, $parent_qty);
    }

    public function test_multiple_dependencies_in_order() {
        // Create another dependent product
        $dependent_product2 = new WC_Product_Simple();
        $dependent_product2->set_name('Second Dependent Product');
        $dependent_product2->set_regular_price('15.00');
        $dependent_product2->set_stock_quantity(5);
        $dependent_product2->set_manage_stock(true);
        $dependent_product2->save();

        // Set up dependency: 3 parent items needed for each dependent item
        update_post_meta($dependent_product2->get_id(), '_wcsd_parent_id', $this->parent_product->get_id());
        update_post_meta($dependent_product2->get_id(), '_wcsd_parent_qty', 3);

        // Add both dependent products to order
        $this->order->add_product($this->dependent_product, 1); // Needs 2 parent items
        $this->order->add_product($dependent_product2, 1); // Needs 3 parent items

        $initial_parent_stock = $this->parent_product->get_stock_quantity();

        // Process the order
        $this->admin->reduce_order_stock($this->order);

        // Refresh parent product data
        $this->parent_product = wc_get_product($this->parent_product->get_id());

        // Total parent items needed: 2 + 3 = 5
        $this->assertEquals($initial_parent_stock - 5, $this->parent_product->get_stock_quantity());

        // Clean up
        $dependent_product2->delete(true);
    }
}
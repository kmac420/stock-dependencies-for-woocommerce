<?php

use StockDependenciesForWooCommerce\Admin\Admin;

class Test_Variable_Product_Dependencies extends WP_UnitTestCase {
    private $parent_product;
    private $variable_product;
    private $variation;
    private $admin;

    public function setUp(): void {
        parent::setUp();
        
        // Create an admin instance
        $this->admin = new Admin();

        // Create a regular product to be the parent dependency
        $this->parent_product = new WC_Product_Simple();
        $this->parent_product->set_name('Parent Product');
        $this->parent_product->set_regular_price('10.00');
        $this->parent_product->set_stock_quantity(10);
        $this->parent_product->save();

        // Create a variable product
        $this->variable_product = new WC_Product_Variable();
        $this->variable_product->set_name('Variable Product');
        $this->variable_product->save();

        // Create attribute
        $attribute = new WC_Product_Attribute();
        $attribute->set_name('Size');
        $attribute->set_options(['Small', 'Medium', 'Large']);
        $attribute->set_position(0);
        $attribute->set_visible(true);
        $attribute->set_variation(true);

        $this->variable_product->set_attributes(array($attribute));
        $this->variable_product->save();

        // Create a variation
        $this->variation = new WC_Product_Variation();
        $this->variation->set_parent_id($this->variable_product->get_id());
        $this->variation->set_attributes(array('size' => 'Small'));
        $this->variation->set_regular_price('20.00');
        $this->variation->set_stock_quantity(5);
        $this->variation->save();
    }

    public function tearDown(): void {
        // Clean up
        $this->parent_product->delete(true);
        $this->variation->delete(true);
        $this->variable_product->delete(true);
        parent::tearDown();
    }

    public function test_add_variation_dependency() {
        // Set up dependency
        update_post_meta($this->variation->get_id(), '_wcsd_parent_id', $this->parent_product->get_id());
        update_post_meta($this->variation->get_id(), '_wcsd_parent_qty', 2);

        // Test if dependency is set correctly
        $parent_id = get_post_meta($this->variation->get_id(), '_wcsd_parent_id', true);
        $parent_qty = get_post_meta($this->variation->get_id(), '_wcsd_parent_qty', true);

        $this->assertEquals($this->parent_product->get_id(), $parent_id);
        $this->assertEquals(2, $parent_qty);
    }

    public function test_variation_stock_status_with_dependency() {
        // Set up dependency: 2 parent items needed for each variation
        update_post_meta($this->variation->get_id(), '_wcsd_parent_id', $this->parent_product->get_id());
        update_post_meta($this->variation->get_id(), '_wcsd_parent_qty', 2);

        // Parent has 10 items, so variation should be in stock (10/2 = 5 possible)
        $stock_status = $this->admin->product_get_stock_status($this->variation->get_stock_status(), $this->variation);
        $this->assertEquals('instock', $stock_status);

        // Set parent stock to 1 (not enough for variation)
        $this->parent_product->set_stock_quantity(1);
        $this->parent_product->save();

        $stock_status = $this->admin->product_get_stock_status($this->variation->get_stock_status(), $this->variation);
        $this->assertEquals('outofstock', $stock_status);
    }

    public function test_variation_stock_quantity_with_dependency() {
        // Set up dependency: 2 parent items needed for each variation
        update_post_meta($this->variation->get_id(), '_wcsd_parent_id', $this->parent_product->get_id());
        update_post_meta($this->variation->get_id(), '_wcsd_parent_qty', 2);

        // Parent has 10 items, variation has 5 items
        // Available stock should be min(5, floor(10/2)) = 5
        $stock_qty = $this->admin->product_get_stock_quantity($this->variation->get_stock_quantity(), $this->variation);
        $this->assertEquals(5, $stock_qty);

        // Set parent stock to 6
        $this->parent_product->set_stock_quantity(6);
        $this->parent_product->save();

        // Available stock should be min(5, floor(6/2)) = 3
        $stock_qty = $this->admin->product_get_stock_quantity($this->variation->get_stock_quantity(), $this->variation);
        $this->assertEquals(3, $stock_qty);
    }

    public function test_multiple_variation_dependencies() {
        // Create another variation
        $variation2 = new WC_Product_Variation();
        $variation2->set_parent_id($this->variable_product->get_id());
        $variation2->set_attributes(array('size' => 'Medium'));
        $variation2->set_regular_price('25.00');
        $variation2->set_stock_quantity(8);
        $variation2->save();

        // Set up dependencies for both variations
        update_post_meta($this->variation->get_id(), '_wcsd_parent_id', $this->parent_product->get_id());
        update_post_meta($this->variation->get_id(), '_wcsd_parent_qty', 2);
        update_post_meta($variation2->get_id(), '_wcsd_parent_id', $this->parent_product->get_id());
        update_post_meta($variation2->get_id(), '_wcsd_parent_qty', 3);

        // Parent has 10 items
        // Variation 1 needs 2 parent items: can have 5 items (10/2)
        // Variation 2 needs 3 parent items: can have 3 items (10/3)
        $stock_qty1 = $this->admin->product_get_stock_quantity($this->variation->get_stock_quantity(), $this->variation);
        $stock_qty2 = $this->admin->product_get_stock_quantity($variation2->get_stock_quantity(), $variation2);

        $this->assertEquals(5, $stock_qty1);
        $this->assertEquals(3, $stock_qty2);

        // Clean up
        $variation2->delete(true);
    }
}
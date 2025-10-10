<?php
/**
 * Stock Dependencies Test Case
 *
 * @package WooCommerce_Stock_Dependencies
 */

/**
 * Class Test_Stock_Dependencies
 */
class Test_Stock_Dependencies extends WP_UnitTestCase {
    /**
     * @var WC_Product_Simple
     */
    private $product;

    /**
     * @var WC_Product_Simple
     */
    private $dependent_product;

    /**
     * @var StockDependenciesForWooCommerceAdmin\Admin
     */
    private $admin;

    /**
     * Set up test case.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create an admin instance
        $this->admin = new StockDependenciesForWooCommerceAdmin\Admin();

        // Create a regular product
        $this->product = new WC_Product_Simple();
        $this->product->set_name('Test Product');
        $this->product->set_regular_price('10.00');
        $this->product->set_stock_quantity(5);
        $this->product->save();

        // Create a dependent product
        $this->dependent_product = new WC_Product_Simple();
        $this->dependent_product->set_name('Dependent Product');
        $this->dependent_product->set_regular_price('20.00');
        $this->dependent_product->set_stock_quantity(10);
        $this->dependent_product->save();
    }

    /**
     * Tear down test case.
     */
    public function tearDown(): void {
        // Clean up
        if ($this->product) {
            $this->product->delete(true);
        }
        if ($this->dependent_product) {
            $this->dependent_product->delete(true);
        }
        parent::tearDown();
    }

    /**
     * Test adding stock dependency.
     */
    public function test_add_stock_dependency() {
        // Set up dependency
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_id', $this->product->get_id());
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_qty', 2);

        // Test if dependency is set correctly
        $parent_id = get_post_meta($this->dependent_product->get_id(), '_wcsd_parent_id', true);
        $parent_qty = get_post_meta($this->dependent_product->get_id(), '_wcsd_parent_qty', true);

        $this->assertEquals($this->product->get_id(), $parent_id);
        $this->assertEquals(2, $parent_qty);
    }

    /**
     * Test stock status with dependency.
     */
    public function test_stock_status_with_dependency() {
        // Set up dependency: 2 parent items needed for each dependent item
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_id', $this->product->get_id());
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_qty', 2);

        // Parent has 5 items, so dependent should be able to sell 2 items (5/2 rounded down)
        $stock_status = $this->admin->product_get_stock_status($this->dependent_product->get_stock_status(), $this->dependent_product);
        $this->assertEquals('instock', $stock_status);

        // Set parent stock to 1 (not enough for dependent product)
        $this->product->set_stock_quantity(1);
        $this->product->save();

        $stock_status = $this->admin->product_get_stock_status($this->dependent_product->get_stock_status(), $this->dependent_product);
        $this->assertEquals('outofstock', $stock_status);
    }

    /**
     * Test stock quantity with dependency.
     */
    public function test_stock_quantity_with_dependency() {
        // Set up dependency: 2 parent items needed for each dependent item
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_id', $this->product->get_id());
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_qty', 2);

        // Parent has 5 items, so dependent should show 2 available (5/2 rounded down)
        $stock_qty = $this->admin->product_get_stock_quantity($this->dependent_product->get_stock_quantity(), $this->dependent_product);
        $this->assertEquals(2, $stock_qty);

        // Set parent stock to 7
        $this->product->set_stock_quantity(7);
        $this->product->save();

        // Should now show 3 available (7/2 rounded down)
        $stock_qty = $this->admin->product_get_stock_quantity($this->dependent_product->get_stock_quantity(), $this->dependent_product);
        $this->assertEquals(3, $stock_qty);
    }

    /**
     * Test is in stock with dependency.
     */
    public function test_is_in_stock_with_dependency() {
        // Set up dependency
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_id', $this->product->get_id());
        update_post_meta($this->dependent_product->get_id(), '_wcsd_parent_qty', 2);

        // Test with sufficient parent stock
        $is_in_stock = $this->admin->product_is_in_stock(true, $this->dependent_product);
        $this->assertTrue($is_in_stock);

        // Test with insufficient parent stock
        $this->product->set_stock_quantity(1);
        $this->product->save();

        $is_in_stock = $this->admin->product_is_in_stock(true, $this->dependent_product);
        $this->assertFalse($is_in_stock);
    }
}
<?php

/**
 * Bootstrap file for setting up tests for Stock Dependencies for WooCommerce
 */

// Load composer autoloader.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// WP_Mock class stub for testing if not yet installed via composer
if (!class_exists('\WP_Mock')) {
    class WP_Mock
    {
        public static function bootstrap() {}
        public static function setUp() {}
        public static function tearDown() {}
        public static function userFunction($function_name, $args = []) {}
        public static function passthruFunction($function_name, $args = []) {}
        public static function onFilter($filter)
        {
            return new WP_Mock_Filter();
        }
        public static function onAction($action)
        {
            return new WP_Mock_Action();
        }
    }

    class WP_Mock_Filter
    {
        public function with()
        {
            return $this;
        }
        public function reply($value)
        {
            return $value;
        }
    }

    class WP_Mock_Action
    {
        public function with()
        {
            return $this;
        }
        public function perform($value = null)
        {
            return $value;
        }
    }
}

// Initialize WP Mock.
WP_Mock::bootstrap();

// Define some global constants needed by the plugin.
define('DAY_IN_SECONDS', 86400);

/**
 * Define WooCommerce mocks we'll need for testing
 */
if (!class_exists('WC_Product')) {
    class WC_Product
    {
        private $id;
        private $sku;
        private $stock_quantity;
        private $meta = [];

        public function __construct($id = 0, $args = [])
        {
            $this->id = $id;
            $this->sku = isset($args['sku']) ? $args['sku'] : '';
            $this->stock_quantity = isset($args['stock_quantity']) ? $args['stock_quantity'] : 0;
        }

        public function get_id()
        {
            return $this->id;
        }

        public function get_sku()
        {
            return $this->sku;
        }

        public function get_stock_quantity()
        {
            return $this->stock_quantity;
        }

        public function set_stock_quantity($quantity)
        {
            $this->stock_quantity = $quantity;
            return $this;
        }

        private $stock_status = '';

        public function set_stock_status($status)
        {
            $this->stock_status = $status;
            return $this;
        }

        public function get_meta($key, $single = true)
        {
            if (isset($this->meta[$key])) {
                return $this->meta[$key];
            }
            return false;
        }

        public function update_meta_data($key, $value)
        {
            $this->meta[$key] = $value;
            return $this;
        }

        public function is_type($type)
        {
            return false;
        }

        public function has_child()
        {
            return false;
        }

        public function managing_stock()
        {
            return true;
        }

        public function is_in_stock()
        {
            return $this->stock_quantity > 0;
        }

        public function backorders_allowed()
        {
            return false;
        }

        public function save()
        {
            return true;
        }

        public function get_name()
        {
            return 'Test Product';
        }

        public function get_parent_id()
        {
            return 0;
        }
    }
}

if (!class_exists('WC_Order')) {
    class WC_Order
    {
        private $id;
        private $items = [];
        private $notes = [];

        public function __construct($id = 0)
        {
            $this->id = $id;
        }

        public function get_id()
        {
            return $this->id;
        }

        public function get_items($type = '')
        {
            return $this->items;
        }

        public function add_order_note($note)
        {
            $this->notes[] = $note;
            return true;
        }

        public function get_notes()
        {
            return $this->notes;
        }

        public function get_qty_refunded_for_item($item_id)
        {
            return 0;
        }
    }
}

if (!class_exists('WC_Order_Item')) {
    class WC_Order_Item
    {
        private $id;
        private $product;
        private $quantity;
        private $meta = [];

        public function __construct($id = 0, $product = null, $quantity = 1)
        {
            $this->id = $id;
            $this->product = $product;
            $this->quantity = $quantity;
        }

        public function get_id()
        {
            return $this->id;
        }

        public function get_product()
        {
            return $this->product;
        }

        public function get_quantity()
        {
            return $this->quantity;
        }

        public function get_meta($key, $single = true)
        {
            if (isset($this->meta[$key])) {
                return $this->meta[$key];
            }
            return false;
        }

        public function meta_exists($key)
        {
            return isset($this->meta[$key]);
        }
    }
}

/**
 * Custom test case for WP_Mock
 */
class WP_Mock_Test_Case extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        \WP_Mock::setUp();
    }

    public function tearDown(): void
    {
        \WP_Mock::tearDown();
    }
}

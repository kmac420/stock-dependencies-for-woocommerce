<?php

/*
Plugin Name: WooCommerce Stock Dependencies
Plugin URI: https://kef.ca
Description: 
Version: 0.1
Author: Kevin McCall
Author URI: https://kef.ca
License: MIT
License URI: https://github.com/kmac420/wc-stock-dependencies/blob/master/LICENSE
*/

namespace WCStockDependencies {

  require_once dirname( __FILE__ ) .'/admin.php';

  add_action( 'woocommerce_product_options_inventory_product_data', 'wcsd_product_options_inventory_product_data' );
  add_action( 'woocommerce_variation_options_pricing', 'wcsd_add_variation_dependency_inventory', 50, 3 );
  add_action( 'woocommerce_admin_process_product_object', 'wcsd_admin_process_product_object'  );
  add_action( 'woocommerce_save_product_variation', 'wcsd_save_product_variation', 10, 2 );
  add_action( 'woocommerce_product_get_stock_quantity', 'wcsd_product_get_stock_quantity', 10, 2 );
  add_action( 'woocommerce_product_variation_get_stock_quantity', 'wcsd_product_get_stock_quantity', 10, 2 );
  add_filter( 'woocommerce_product_is_in_stock', 'wcsd_product_is_in_stock', 10, 2 );
  add_filter( 'woocommerce_variation_is_in_stock', 'wcsd_product_is_in_stock', 10, 2 );
  add_filter( 'woocommerce_product_get_stock_status', 'wcsd_product_get_stock_status', 10, 2 );
  add_filter( 'woocommerce_product_variation_get_stock_status', 'wcsd_product_get_stock_status', 10, 2 );
  add_filter( 'woocommerce_reduce_order_stock', 'wcsd_reduce_order_stock', 10, 1);
  add_action( 'admin_enqueue_scripts', 'wcsd_enqueu_scripts' );

}

?>

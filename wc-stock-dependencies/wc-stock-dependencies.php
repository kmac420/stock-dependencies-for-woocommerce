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

  use WooCommerceStockDependenciesAdmin as Admin;

  add_action( 'woocommerce_product_options_inventory_product_data', array( new Admin\Admin(), 'product_options_inventory_product_data' ), 10, 1 );
  add_action( 'woocommerce_variation_options_pricing', array( new Admin\Admin(), 'add_variation_dependency_inventory' ), 10, 3 );
  add_action( 'woocommerce_admin_process_product_object', array( new Admin\Admin(), 'admin_process_product_object' ), 10, 1 );
  add_action( 'woocommerce_save_product_variation', array( new Admin\Admin(), 'save_product_variation' ), 10, 2 );
  add_action( 'woocommerce_product_get_stock_quantity', array( new Admin\Admin(), 'product_get_stock_quantity' ), 10, 2 );
  add_action( 'woocommerce_product_variation_get_stock_quantity', array( new Admin\Admin(), 'product_get_stock_quantity' ), 10, 2 );
  add_filter( 'woocommerce_product_is_in_stock', array( new Admin\Admin(), 'product_is_in_stock' ), 10, 2 );
  add_filter( 'woocommerce_variation_is_in_stock', array( new Admin\Admin(), 'product_is_in_stock' ), 10, 2 );
  add_filter( 'woocommerce_product_get_stock_status', array( new Admin\Admin(), 'product_get_stock_status' ), 10, 2 );
  add_filter( 'woocommerce_product_variation_get_stock_status', array( new Admin\Admin(), 'product_get_stock_status' ), 10, 2 );
  add_filter( 'woocommerce_reduce_order_stock', array( new Admin\Admin(), 'reduce_order_stock' ), 10, 1 );
  add_filter( 'woocommerce_hidden_order_itemmeta', array(new Admin\Admin(), 'hidden_order_itemmeta' ), 50, 1);
  add_action( 'admin_enqueue_scripts', array( new Admin\Admin(), 'enqueu_scripts' ) );

}

?>

<?php

/*
Plugin Name: Stock Dependencies for WooCommerce
Plugin URI: https://github.com/kmac420/stock-dependencies-for-woocommerce
Description: Make the products and variations in your WooCommerce store dependent on the inventory of your other products or variations with Stock Dependencies for WooCommerce.
Version: 2.0.0
Author: Kevin McCall
Author URI: https://kef.ca
License: MIT
License URI: https://github.com/kmac420/stock-dependencies-for-woocommerce/blob/master/LICENSE
WC requires at least: 4.0
WC tested up to: 8.7
*/

namespace StockDependenciesForWooCommerce {

  require_once dirname(__FILE__) . '/admin.php';

  use StockDependenciesForWooCommerceAdmin as Admin;

  add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
      \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
  });

  // Initialize single instance of Admin class
  function get_admin_instance() {
    static $admin = null;
    if ($admin === null) {
      $admin = new Admin\Admin();
    }
    return $admin;
  }

  $admin = get_admin_instance();

  add_action('admin_menu', array($admin, 'settings_page'));
  add_action('woocommerce_product_options_inventory_product_data', array($admin, 'product_options_inventory_product_data'), 10, 1);
  add_action('woocommerce_variation_options_pricing', array($admin, 'add_variation_dependency_inventory'), 10, 3);
  add_action('woocommerce_admin_process_product_object', array($admin, 'admin_process_product_object'), 10, 1);
  add_action('woocommerce_save_product_variation', array($admin, 'save_product_variation'), 10, 2);
  add_action('woocommerce_product_get_stock_quantity', array($admin, 'product_get_stock_quantity'), 10, 2);
  add_action('woocommerce_product_variation_get_stock_quantity', array($admin, 'product_get_stock_quantity'), 10, 2);
  add_action('woocommerce_before_save_order_items', array($admin, 'before_save_order_items'), 10, 2);
  add_action('woocommerce_restock_refunded_item', array($admin, 'restock_refunded_item'), 10, 5);
  add_action('woocommerce_order_status_cancelled', array($admin, 'restock_cancelled_order'), 10, 5);
  add_action('woocommerce_after_order_itemmeta', array($admin, 'display_item_dependencies_in_admin'), 10, 3);
  add_filter('woocommerce_product_is_in_stock', array($admin, 'product_is_in_stock'), 10, 2);
  add_filter('woocommerce_variation_is_in_stock', array($admin, 'product_is_in_stock'), 10, 2);
  add_filter('woocommerce_product_get_stock_status', array($admin, 'product_get_stock_status'), 10, 2);
  add_filter('woocommerce_product_variation_get_stock_status', array($admin, 'product_get_stock_status'), 10, 2);
  add_filter('woocommerce_reduce_order_stock', array($admin, 'reduce_order_stock'), 10, 1);
  add_filter('woocommerce_hidden_order_itemmeta', array($admin, 'hidden_order_itemmeta'), 50, 1);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($admin, 'action_links'), 10, 1);
  add_action('admin_enqueue_scripts', array($admin, 'enqueu_scripts'));
}

<?php

/*
Plugin Name: Stock Dependencies for WooCommerce
Plugin URI: https://github.com/kmac420/stock-dependencies-for-woocommerce
Description: Make the products and variations in your WooCommerce store dependent on the inventory of your other products or variations with Stock Dependencies for WooCommerce.
Version: 1.6.2
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

  add_action('admin_menu', array(new Admin\Admin(), 'settings_page'));
  add_action('woocommerce_product_options_inventory_product_data', array(new Admin\Admin(), 'product_options_inventory_product_data'), 10, 1);
  add_action('woocommerce_variation_options_pricing', array(new Admin\Admin(), 'add_variation_dependency_inventory'), 10, 3);
  add_action('woocommerce_admin_process_product_object', array(new Admin\Admin(), 'admin_process_product_object'), 10, 1);
  add_action('woocommerce_save_product_variation', array(new Admin\Admin(), 'save_product_variation'), 10, 2);
  add_action('woocommerce_product_get_stock_quantity', array(new Admin\Admin(), 'product_get_stock_quantity'), 10, 2);
  add_action('woocommerce_product_variation_get_stock_quantity', array(new Admin\Admin(), 'product_get_stock_quantity'), 10, 2);
  add_action('woocommerce_before_save_order_items', array(new Admin\Admin(), 'before_save_order_items'), 10, 2);
  add_action('woocommerce_restock_refunded_item', array(new Admin\Admin(), 'restock_refunded_item'), 10, 5);
  add_action('woocommerce_order_status_cancelled', array(new Admin\Admin(), 'restock_cancelled_order'), 10, 5);
  add_action('woocommerce_after_order_itemmeta', array(new Admin\Admin(), 'display_item_dependencies_in_admin'), 10, 3);
  add_filter('woocommerce_product_is_in_stock', array(new Admin\Admin(), 'product_is_in_stock'), 10, 2);
  add_filter('woocommerce_variation_is_in_stock', array(new Admin\Admin(), 'product_is_in_stock'), 10, 2);
  add_filter('woocommerce_product_get_stock_status', array(new Admin\Admin(), 'product_get_stock_status'), 10, 2);
  add_filter('woocommerce_product_variation_get_stock_status', array(new Admin\Admin(), 'product_get_stock_status'), 10, 2);
  add_filter('woocommerce_reduce_order_stock', array(new Admin\Admin(), 'reduce_order_stock'), 10, 1);
  add_filter('woocommerce_hidden_order_itemmeta', array(new Admin\Admin(), 'hidden_order_itemmeta'), 50, 1);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(new Admin\Admin(), 'action_links'), 10, 1);
  add_action('admin_enqueue_scripts', array(new Admin\Admin(), 'enqueu_scripts'));
}

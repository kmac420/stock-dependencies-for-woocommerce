<?php

namespace StockDependenciesForWooCommerceAdmin\Admin;

/**
 * Manages stock dependency metadata and transient caching
 */
class DependencyManager
{
  /**
   * Get the product object by SKU
   *
   * @param string $sku
   * @return WC_Product|false
   */
  public function get_product_by_sku($sku)
  {
    $product_id = wc_get_product_id_by_sku($sku);
    if ($product_id) {
      return wc_get_product($product_id);
    }
    return false;
  }

  /**
   * Get stock dependency meta for a product
   *
   * @param \WC_Product $product
   * @return string|false
   */
  public function get_stock_dependency_meta($product)
  {
    if ($product->get_meta('_stock_dependency')) {
      $product_meta = $product->get_meta('_stock_dependency');
      return $this->update_product_data($product_meta);
    }
    return false;
  }

  /**
   * Get stock dependency for an order item
   *
   * @param \WP_Order_Item $item
   * @return object|false
   */
  public function get_order_item_stock_dependencies($item)
  {
    if ($item->get_meta('_stock_dependency')) {
      $meta = $item->get_meta('_stock_dependency');
      return !empty($meta) ? json_decode($meta) : false;
    }
    return false;
  }

  /**
   * Get refunded quantity for an order item
   *
   * @param \WP_Order_Item $item
   * @return int|false
   */
  public function get_order_item_refunded_qty($item)
  {
    if ($item->get_meta('_stock_dependency_restocked')) {
      return $item->get_meta('_stock_dependency_restocked');
    }
    return false;
  }

  /**
   * Validate that product data is proper JSON
   *
   * @param string $product_data
   * @return bool
   */
  public function validate_product_data($product_data)
  {
    if (empty($product_data)) {
      return false;
    }
    json_decode($product_data);
    return (json_last_error() == JSON_ERROR_NONE);
  }

  /**
   * Update product data to include product ID and validate SKU
   *
   * @param string $product_data
   * @return string
   */
  public function update_product_data($product_data)
  {
    if (!empty($product_data) && $product_data != '') {
      $meta_updated = false;
      $stock_dependency_settings = json_decode($product_data);
      foreach ($stock_dependency_settings->stock_dependency as $key => $stock_dependency) {
        if (!isset($stock_dependency->product_id)) {
          if ($this->get_product_by_sku($stock_dependency->sku)) {
            $stock_dependency_product = $this->get_product_by_sku($stock_dependency->sku);
            $stock_dependency->product_id = $stock_dependency_product->get_id();
          } else {
            unset($stock_dependency_settings->stock_dependency[$key]);
          }
          $meta_updated = true;
        } else {
          $stock_dependency_product = wc_get_product($stock_dependency->product_id);
          if ($stock_dependency_product->get_sku() != $stock_dependency->sku) {
            $stock_dependency->sku = $stock_dependency_product->get_sku();
            $meta_updated = true;
          }
        }
      }
      if (count($stock_dependency_settings->stock_dependency) == 0) {
        $stock_dependency_settings->enabled = false;
        $meta_updated = true;
      }
      if ($meta_updated) {
        $product_data = json_encode($stock_dependency_settings);
      }
    }
    return $product_data;
  }

  /**
   * Save stock dependency settings in a database transient
   *
   * @param \WC_Product $product
   * @param string $product_data
   */
  public function save_dependency_transient($product, $product_data)
  {
    $transient_id = 'sdwc-product-settings-' . $product->get_id();
    set_transient($transient_id, $this->update_product_data($product_data), 7 * DAY_IN_SECONDS);
  }

  /**
   * Get stock dependency settings from transient or meta
   *
   * @param \WC_Product $product
   * @return object|false
   */
  public function get_stock_dependency_settings($product)
  {
    $transient_id = 'sdwc-product-settings-' . $product->get_id();
    if (false !== ($transient_value = get_transient($transient_id))) {
      $stock_dependency_settings = $transient_value ? json_decode($transient_value) : false;
    } else if (false !== ($stock_dependency_settings_string = $this->get_stock_dependency_meta($product))) {
      $this->save_dependency_transient($product, $stock_dependency_settings_string);
      $stock_dependency_settings = $stock_dependency_settings_string ? json_decode($stock_dependency_settings_string) : false;
    } else {
      return false;
    }
    return $stock_dependency_settings;
  }

  /**
   * Check if product has stock dependencies
   *
   * @param \WC_Product $product
   * @return bool
   */
  public function has_stock_dependencies($product)
  {
    if (!$product) {
      return false;
    }

    if (false !== ($stock_dependency_settings = $this->get_stock_dependency_settings($product))) {
      if (is_object($stock_dependency_settings) && isset($stock_dependency_settings->enabled) && $stock_dependency_settings->enabled) {
        return true;
      }
    }
    return false;
  }

  /**
   * Get all stock dependency settings from database
   *
   * @return array
   */
  public function get_all_stock_dependency_settings()
  {
    global $wpdb;

    $meta_values = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
        '_stock_dependency'
      )
    );

    return $meta_values;
  }

  /**
   * Delete all stock dependency transients
   */
  public function delete_all_stock_dependency_transients()
  {
    global $wpdb;

    $query_results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT count(*) AS num_transients FROM {$wpdb->options} WHERE option_name LIKE %s",
        '_transient_sdwc-product-settings%'
      )
    );

    $num_transients = $query_results[0]->num_transients;

    echo ("<p>Clearing transients ... ");
    if ($num_transients == 0) {
      echo ("No transients to clear");
    } else {
      $wpdb->query(
        $wpdb->prepare(
          "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
          '_transient_sdwc-product-settings%'
        )
      );

      $query_results = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT count(*) AS num_transients FROM {$wpdb->options} WHERE option_name LIKE %s",
          '_transient_sdwc-product-settings%'
        )
      );
      echo ("<span style=\"color:green;\">Done!</span>");
    }
    echo ("</p>");
  }
}

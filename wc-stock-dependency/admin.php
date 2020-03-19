<?php
/* Custom Stock Dependency */

 /**
  * 
  * @param string $sku
  *
  * Get the product object by SKU
  *
  */

function wcsd_get_product_by_sku($sku) {
  $product_id = wc_get_product_id_by_sku($sku);
  // error_log('product_id from sku: ' . $product_id);
  if ($product_id) {
    return wc_get_product($product_id);
  } else {
    return false;
  }
}

/**
 * 
 * @param WC_Product $product
 * 
 * Add the stock dependency field for simple products
 * 
 */

function wcsd_product_options_inventory_product_data( $product ) {

  global $post;
  if ( $post->post_type == "product" ) {
    $product = wc_get_product($post->ID);
    woocommerce_wp_hidden_input( array(
	    'id'     => 'wcsd_product_stock_dependency',
      'class'  => "wcsd_product_stock_dependency",
      'name'   => "wcsd_product_stock_dependency",
      'value'  => $product->get_meta('_stock_dependency') ?? '',
    ) );
  }
}

add_action( 'woocommerce_product_options_inventory_product_data', 'wcsd_product_options_inventory_product_data' );

/**
 * Add variable setting.
 *
 * @param $loop
 * @param $variation_data
 * @param $variation
 */

function wcsd_add_variation_dependency_inventory( $loop, $variation_data, $variation ) {

  $variation = wc_get_product( $variation );
  woocommerce_wp_hidden_input( array(
    'id'     => "wcsd_variation_stock_dependency-{$loop}",
    'class'  => "wcsd_variation_stock_dependency",
    'name'   => "wcsd_variation_stock_dependency[{$loop}]",
    'value'  => $variation->get_meta('_stock_dependency') ?? '',
	) );

}

add_action( 'woocommerce_variation_options_pricing', 'wcsd_add_variation_dependency_inventory', 50, 3 );

/**
 * Save the custom fields.
 *
 * @param WC_Product $product
 */

function wcsd_admin_process_product_object( $product ) {

  if ( ! empty( $_POST['wcsd_product_stock_dependency'] ) ) {
    $product->update_meta_data( '_stock_dependency', stripslashes($_POST['wcsd_product_stock_dependency']));
  }
}

add_action( 'woocommerce_admin_process_product_object', 'wcsd_admin_process_product_object'  );

/**
 * 
 * Save custom variable fields.
 *
 * @param int $variation_id
 * @param int $i
 * 
 */

function wcsd_save_product_variation( $variation_id, $i ) {
  $variation = wc_get_product( $variation_id );
  if ( ! empty( $_POST['wcsd_variation_stock_dependency-'.$i] ) ) {
    $variation->update_meta_data( '_stock_dependency', stripslashes($_POST['wcsd_variation_stock_dependency-'.$i ]));
    $variation->save();
  }
}

add_action( 'woocommerce_save_product_variation', 'wcsd_save_product_variation', 10, 2 );

/**
 * 
 * @param int $quantity
 * @param WC_Product $product
 * 
 * Get the stock quantity of the product/variation by checking the stock quanties of the 
 * dependency products/variations and using the minimum of those. If there are no dependency
 * products/variations then simply return the product's/variation's actual quantity
 * 
 */

function wcsd_product_get_stock_quantity($quantity, $product) {
  error_log('wcsd_product_get_stock_quantity,' . microtime(true) . ',' . $product->get_id() . ',0,Getting stock quantity for product : ' . $product->get_id());
  if ($product->get_meta( '_stock_dependency')) {
    $stock_dependency_settings = json_decode($product->get_meta('_stock_dependency'));
    if ( $stock_dependency_settings->enabled) {
      foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
        if ($stock_dependency->sku) {
          error_log('wcsd_product_get_stock_quantity,' . microtime(true) . ',' . $product->get_id() . ',1,Getting stock quantity for dependency SKU: ' . $stock_dependency->sku);
          if (wcsd_get_product_by_sku($stock_dependency->sku)) {
            $dependency_product = wcsd_get_product_by_sku($stock_dependency->sku);
            if ($dependency_product) {
              $dependency_product_available = $dependency_product->get_stock_quantity();
              if ( !isset($temp_stock_quantity)) {
                // $stock_dependency->qty should always be a positive, non-zero integer
                $temp_stock_quantity = intdiv($dependency_product_available, $stock_dependency->qty);
                error_log('wcsd_product_get_stock_quantity,' . microtime(true) . ',' . $product->get_id() . ',2,Stock for dependency SKU: ' . $stock_dependency->sku . ' Required per order item: ' . $stock_dependency->qty . ' Order qty: ' . $quantity);
              } else {
                $temp_stock_quantity = min($temp_stock_quantity, intdiv($dependency_product_available, $stock_dependency->qty));
              }
            }
          } else {
            $temp_stock_quantity = 0;
            break;
          }
        }
      }
      $quantity = $temp_stock_quantity;
    }
  }
  error_log('wcsd_product_get_stock_quantity,' . microtime(true) . ',' . $product->get_id() . ',4,Stock quantity for product: ' . $product->get_id() . ' is: ' . $quantity);
  return $quantity;
}

add_action( 'woocommerce_product_get_stock_quantity', 'wcsd_product_get_stock_quantity', 10, 2 );
add_action( 'woocommerce_product_variation_get_stock_quantity', 'wcsd_product_get_stock_quantity', 10, 2 );

/**
 * 
 * @param bool $is_in_stock
 * @param WC_Product $product
 * 
 * Get the in-stock status of the product/variation by checking the stock levels of the 
 * dependency products/variations. If there are no stock dependency settings then simply return
 * the product's/variation's actual in-stock status
 * 
 */

function wcsd_product_is_in_stock($is_in_stock, $product) {
  error_log('wcsd_product_is_in_stock,' . microtime(true) . ',' . $product->get_id() . ',0,Getting is_in_stock for: ' . $product->get_id() . ' with initial is_in_stock: ' . $is_in_stock);
  if ($product->get_meta( '_stock_dependency')) {
    $stock_dependency_settings = json_decode($product->get_meta('_stock_dependency'));
    if ( $stock_dependency_settings->enabled) {
      foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
        if ($stock_dependency->sku) {
          error_log('wcsd_product_is_in_stock,' . microtime(true) . ',' . $product->get_id() . ',1,Getting is_in_stock for dependency SKU: ' . $stock_dependency->sku);
          if (wcsd_get_product_by_sku($stock_dependency->sku)) {
            $dependency_product = wcsd_get_product_by_sku($stock_dependency->sku);
            $dependency_product_available = $dependency_product->get_stock_quantity();
            error_log('wcsd_product_is_in_stock,' . microtime(true) . ',' . $product->get_id() . ',2,is_in_stock for dependency SKU: ' . $stock_dependency->sku . ' Required: ' . $stock_dependency->qty . ' Available: ' . $dependency_product_available);
            if (intdiv($dependency_product_available, $stock_dependency->qty) === 0) {
              $is_in_stock = false;
              break;
            } elseif (intdiv($dependency_product_available, $stock_dependency->qty) > 0) {
              $is_in_stock = true;
            }
          } else {
            $is_in_stock =false;
            break;
          }
        }
      }
    }
  }
  error_log('wcsd_product_is_in_stock,' . microtime(true) . ',' . $product->get_id() . ',4,is_in_stock for product: ' . $product->get_id() . ' is (string): ' . $is_in_stock);
  return $is_in_stock;
}

add_filter( 'woocommerce_product_is_in_stock', 'wcsd_product_is_in_stock', 10, 2 );
add_filter( 'woocommerce_variation_is_in_stock', 'wcsd_product_is_in_stock', 10, 2 );

/**
 * 
 * @param string $status
 * @param WC_Product $product
 * 
 * hook filter: woocommerce_product_variation_get_stock_status
 * 
 * Get the stock status of the product/variation by checking the stock statuses of the 
 * dependency products/variations. If there are no dependency products/variations then simply
 * return the product's/variation's actual stock status
 * 
 */

function wcsd_product_get_stock_status($status, $product) {
  // error_log('$product');
  // error_log(print_r($product, true));
  error_log('wcsd_product_get_stock_status,' . microtime(true) . ',' . $product->get_id() . ',0,Getting stock status for: ' . $product->get_id());
  if ($product->get_meta( '_stock_dependency')) {
    $stock_dependency_settings = json_decode($product->get_meta('_stock_dependency'));
    // error_log('$stock_dependency_settings');
    // error_log(print_r($stock_dependency_settings, true));
    if ( $stock_dependency_settings->enabled) {
      foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
        if ($stock_dependency->sku) {
          error_log('wcsd_product_get_stock_status,' . microtime(true) . ',' . $product->get_id() . ',1,Getting stock status for dependency SKU: ' . $stock_dependency->sku);
          if (wcsd_get_product_by_sku($stock_dependency->sku)) {
            $dependency_product = wcsd_get_product_by_sku($stock_dependency->sku);
            $dependency_product_available = $dependency_product->get_stock_quantity();
            // error_log('$dependency_product_available');
            // error_log(print_r($dependency_product_available, true));
            error_log('wcsd_product_get_stock_status,' . microtime(true) . ',' . $product->get_id() . ',2,Stock for dependency SKU: ' . $stock_dependency->sku . ' Required: ' . $stock_dependency->qty);
            if (intdiv($dependency_product_available, $stock_dependency->qty) === 0) {
              $status = "outofstock";
              break;
            } elseif (intdiv($dependency_product_available, $stock_dependency->qty) > 0) {
              $status = "instock";
              break;
            }
          }
        } else {
          $status = "outofstock";
          break;
        }
      }
    }
  }
  error_log('wcsd_product_get_stock_status,' . microtime(true) . ',' . $product->get_id() . ',3,Stock status for product: ' . $product->get_id() . ' is: ' . $status);
  return $status;
}

add_filter( 'woocommerce_product_get_stock_status', 'wcsd_product_get_stock_status', 10, 2 );
add_filter( 'woocommerce_product_variation_get_stock_status', 'wcsd_product_get_stock_status', 10, 2 );

/**
 * hook action: wcsd_reduce_order_stock
 * 
 * @param WC_Order $order
 * 
 * This action hook is called after the stock has been reduced for the order
 * being checked out. This function will reduce the inventory of the dependency
 * stock items by the number of items in the order times the number of dependency
 * stock for that item. Note that if the item in the order being checked out 
 * has dependency products, then the WooCommerce function wc_reduce_order_stock
 * will set the inventory quantity of that product to zero (0).
 * Once the 0-quantity reduction is complete, this function will be
 * called and will reduce the stock for the dependency products.
 * 
 */

function wcsd_reduce_order_stock($order) {
  $items = $order->get_items();
  // check each order item to see if there is stock dependency settings
  foreach ( $items as $item ) {
    // error_log('$item');
    // error_log(print_r($item, true));
    $order_product = wc_get_product( $item['product_id'] );
    if ( $order_product->is_type('variable')) {
      $order_product = wc_get_product( $item['variation_id'] );
    }
    if ($order_product->get_meta( '_stock_dependency')) {
      $stock_dependency_settings_string = $order_product->get_meta('_stock_dependency');
      $stock_dependency_settings = json_decode($stock_dependency_settings_string);
      $order_item_qty = $item->get_quantity();
      if ( $stock_dependency_settings->enabled) {
        // for each stock dependency sku, decrease the stock by the correct amount
        // and create a note on the order
        foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
          if ($stock_dependency->sku) {
            $dependency_product = wcsd_get_product_by_sku($stock_dependency->sku);
            $new_stock = wc_update_product_stock(
              $dependency_product,
              $order_item_qty * $stock_dependency->qty,
              'decrease' );
            if ( is_wp_error( $new_stock ) ) {
              $order->add_order_note( sprintf(
                __('Unable to reduce stock for dependency SKU %s from %s by quantity %s', 'woocommerce' ),
                $dependency_product->get_sku(),
                $dependency_product->get_stock_quantity(),
                $order_item_qty * $stock_dependency->qty )
              );
            } else {
              $order->add_order_note( sprintf(
                __('Reduced order stock for dependency SKU %s from %s by quantity %s', 'woocommerce' ),
                $dependency_product->get_sku(),
                $dependency_product->get_stock_quantity(),
                $order_item_qty * $stock_dependency->qty )
              );
            }
          }
        }
        // reset the ordered item stock level to 0
        $new_stock = wc_update_product_stock( $order_product, 0, 'set' );
        $order_product_sku = $order_product->get_sku();
        if ( is_wp_error( $new_stock ) ) {
          $order->add_order_note( sprintf(
            __('Unable to set stock for SKU %s to 0', 'woocommerce' ),
            $order_product_sku )
          );
        } else {
          $order->add_order_note( sprintf(
            __('Set order stock for SKU %s to 0', 'woocommerce' ),
            $order_product_sku )
          );
        }
      }
      // Add the stock dependency settings to the order item so that if a return
      // is processed we will know the stock dependency settings that were used
      // for this order item and not assume that the stock dependency settings 
      // have not changed
      $add_order_item_meta = wc_add_order_item_meta(
        $item->get_id(),
        '_stock_dependency',
        $stock_dependency_settings_string,
        false
      );
    }
  }
}

add_filter( 'woocommerce_reduce_order_stock', 'wcsd_reduce_order_stock', 10, 1);

function wcsd_enqueu_scripts($hook) {
  // Only add to the edit.php admin page.
  // See WP docs.
  // if ('edit.php' !== $hook) {
  //   return;
  // }
  wp_enqueue_script('wcsd_admin_settings', plugins_url("/settings.js", __FILE__));
  wp_enqueue_style('wcsd_admin_styles', plugins_url("/admin.css", __FILE__));
}

add_action('admin_enqueue_scripts', 'wcsd_enqueu_scripts');

<?php
/* Custom Stock Required */

/* 
 * Get the product object by SKU
 */
function wcrs_get_product_by_sku($sku) {
  $product_id = wc_get_product_id_by_sku($sku);
  if ($product_id) {
    return wc_get_product($product_id);
  } else {
    return false;
  }
}

/**
 * Simple product setting.
 */
function wcrs_product_options_inventory_product_data( $product ) {

  global $post;
  if ( $post->post_type == "product" ) {
    $product = wc_get_product($post->ID);
    woocommerce_wp_hidden_input( array(
	    'id'     => 'wcrs_product_required_stock',
      'class'  => "wcrs_product_required_stock",
      'name'   => "wcrs_product_required_stock",
      'value'  => $product->get_meta('_required_stock') ?? '',
    ) );
  }
}

add_action( 'woocommerce_product_options_inventory_product_data', 'wcrs_product_options_inventory_product_data' );

/**
 * Add variable setting.
 *
 * @param $loop
 * @param $variation_data
 * @param $variation
 */

function wcrs_add_variation_required_inventory( $loop, $variation_data, $variation ) {

  $variation = wc_get_product( $variation );
  woocommerce_wp_hidden_input( array(
    'id'     => "wcrs_variation_required_stock-{$loop}",
    'class'  => "wcrs_variation_required_stock",
    'name'   => "wcrs_variation_required_stock[{$loop}]",
    'value'  => $variation->get_meta('_required_stock') ?? '',
	) );

}

add_action( 'woocommerce_variation_options_pricing', 'wcrs_add_variation_required_inventory', 50, 3 );

/**
 * Save the custom fields.
 *
 * @param WC_Product $product
 */
function wcrs_admin_process_product_object( $product ) {

  if ( ! empty( $_POST['wcrs_product_required_stock'] ) ) {
    $product->update_meta_data( '_required_stock', stripslashes($_POST['wcrs_product_required_stock']));
  }
}

add_action( 'woocommerce_admin_process_product_object', 'wcrs_admin_process_product_object'  );

/**
 * Save custom variable fields.
 *
 * @param int $variation_id
 * @param $i
 */

function wcrs_save_product_variation( $variation_id, $i ) {
  $variation = wc_get_product( $variation_id );
  if ( ! empty( $_POST['wcrs_variation_required_stock-'.$i] ) ) {
    $variation->update_meta_data( '_required_stock', stripslashes($_POST['wcrs_variation_required_stock-'.$i ]));
    $variation->save();
  }
}

add_action( 'woocommerce_save_product_variation', 'wcrs_save_product_variation', 10, 2 );

/**
 * Get the stock quantity of the product/variation by checking the stock quanties of the 
 * required products/variations and using the minimum of those. If there are no required
 * products/variations then simply return the product's/variation's actual quantity
 */

function wcrs_product_get_stock_quantity($quantity, $product) {
  if ($product->get_meta( '_required_stock')) {
    $required_stock_settings = json_decode($product->get_meta('_required_stock'));
    if ( $required_stock_settings->enabled) {
      foreach ($required_stock_settings->required_stock as $required_stock) {
        if ($required_stock->sku) {
          $required_product = wcrs_get_product_by_sku($required_stock->sku);
          if ($required_product) {
            $required_product_available = $required_product->get_stock_quantity();
            if ( !isset($temp_stock_quantity)) {
              // $required_stock->qty should always be a positive, non-zero integer
              $temp_stock_quantity = intdiv($required_product_available, $required_stock->qty);
            } else {
              $temp_stock_quantity = min($temp_stock_quantity, intdiv($required_product_available, $required_stock->qty));
            }
          }
        }
      }
      $quantity = $temp_stock_quantity;
    }
  }
 return $quantity;
}

add_action( 'woocommerce_product_get_stock_quantity', 'wcrs_product_get_stock_quantity', 10, 2 );
add_action( 'woocommerce_product_variation_get_stock_quantity', 'wcrs_product_get_stock_quantity', 10, 2 );

/**
 * Get the in-stock status of the product/variation by checking the stock levels of the 
 * required products/variations. If there are no required stock then simply return
 * the product's/variation's actual in-stock status
 */

function wcrs_product_is_in_stock($is_in_stock, $product) {
  if ($product->get_meta( '_required_stock')) {
    $required_stock_settings = json_decode($product->get_meta('_required_stock'));
    if ( $required_stock_settings->enabled) {
      foreach ($required_stock_settings->required_stock as $required_stock) {
        if ($required_stock->sku) {
          $required_product = wcrs_get_product_by_sku($required_stock->sku);
          $required_product_available = $required_product->get_stock_quantity();
          if (intdiv($required_product_available, $required_stock->qty) === 0) {
            $is_in_stock = false;
            break;
          }
        }
      }
    }
  }
  return $is_in_stock;
}

add_filter( 'woocommerce_product_is_in_stock', 'wcrs_product_is_in_stock', 10, 2 );
add_filter( 'woocommerce_variation_is_in_stock', 'wcrs_product_is_in_stock', 10, 2 );

/**
 * hook filter: woocommerce_product_variation_get_stock_status
 * 
 * Get the stock status of the product/variation by checking the stock statuses of the 
 * required products/variations. If there are no required products/variations then simply
 * return the product's/variation's actual stock status
 */

function wcrs_product_get_stock_status( $status, $product) {
  if ($product->get_meta( '_required_stock')) {
    $required_stock_settings = json_decode($product->get_meta('_required_stock'));
    if ( $required_stock_settings->enabled) {
      foreach ($required_stock_settings->required_stock as $required_stock) {
        if ($required_stock->sku) {
          $required_product = wcrs_get_product_by_sku($required_stock->sku);
          $required_product_available = $required_product->get_stock_quantity();
          if (intdiv($required_product_available, $required_stock->qty) === 0) {
            $status = "outofstock";
            break;
          }
        }
      }
    }
  }
  return $status;
}

add_filter( 'woocommerce_product_get_stock_status', 'wcrs_product_get_stock_status', 10, 2 );
add_filter( 'woocommerce_product_variation_get_stock_status', 'wcrs_product_get_stock_status', 10, 2 );

/**
 * hook action: wcrs_reduce_order_stock
 * 
 * @param WC_Order $order
 * 
 * This action hook is called after the stock has been reduced for the order
 * being checked out. This function will reduce the inventory of the required
 * stock items by the number of items in the order times the number of required
 * stock for that item. Note that if the item in the order being checked out 
 * has required products, then the WooCommerce function wc_reduce_order_stock
 * will set the inventory quantity of that product to zero (0).
 * Once the 0-quantity reduction is complete, this function will be
 * called and will reduce the stock for the required products.
 * 
 */

function wcrs_reduce_order_stock($order) {
  $items = $order->get_items();
  // check each order item to see if there is required stock settings
  foreach ( $items as $item ) {
    $order_product = wc_get_product( $item['product_id'] );
    if ( $order_product->is_type('variable')) {
      $order_product = wc_get_product( $item['variation_id'] );
    }
    if ($order_product->get_meta( '_required_stock')) {
      $required_stock_settings_string = $order_product->get_meta('_required_stock');
      $required_stock_settings = json_decode($required_stock_settings_string);
      $order_item_qty = $item->get_quantity();
      if ( $required_stock_settings->enabled) {
        // for each required stock sku, decrease the stock by the correct amount
        // and create a note on the order
        foreach ($required_stock_settings->required_stock as $required_stock) {
          if ($required_stock->sku) {
            $required_product = wcrs_get_product_by_sku($required_stock->sku);
            $new_stock = wc_update_product_stock(
              $required_product,
              $order_item_qty * $required_stock->qty,
              'decrease' );
            if ( is_wp_error( $new_stock ) ) {
              $order->add_order_note( sprintf(
                __('Unable to reduce stock for required SKU %s from %s by quantity %s', 'woocommerce' ),
                $required_product->get_sku(),
                $required_product->get_stock_quantity(),
                $order_item_qty * $required_stock->qty )
              );
            } else {
              $order->add_order_note( sprintf(
                __('Reduced order stock for required SKU %s from %s by quantity %s', 'woocommerce' ),
                $required_product->get_sku(),
                $required_product->get_stock_quantity(),
                $order_item_qty * $required_stock->qty )
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
      // Add the required stock settings to the order item so that if a return
      // is processed we will know the required stock settings that were used
      // for this order item and not assume that the required stock settings 
      // have not changed
      $add_order_item_meta = wc_add_order_item_meta(
        $item-get_id(),
        '_required_stock',
        $required_stock_settings_string,
        false
      );
    }
  }
}

add_filter( 'woocommerce_reduce_order_stock', 'wcrs_reduce_order_stock', 10, 1);

/**
 * 
 * THE HOOK CALLING THIS FUNCTION IS CURRENTLY DISABLED
 * 
 * hook filter: woocommerce_order_item_quantity
 * 
 * @param int $qty
 * @param WC_Order $order
 * @param WC_Order_Item $item
 * 
 * This filter hook is called to allow the quantity of an item in an order to be
 * changed during checkout. If the product or product variation has dependancies,
 * then return a quantity of 0 as we are not changing the stock level for this item.
 * 
 */

function wcrs_order_item_quantity($qty, $order, $item) {
  $product = wc_get_product( $item['product_id'] );
  if ( $product->is_type('variable')) {
    $product = wc_get_product( $item['variation_id'] );
  }
  if ( $product->get_meta( '_required_product') ) {
    // Return a 0 so we don't reduce the stock if it has required products
    $qty = 0;
    $item_name = $product->get_formatted_name();
    $order->add_order_note( sprintf( __( 'Stock level for %s is not reduced as it has required products.', 'woocommerce' ), $item_name ) );
  }
  return $qty;
}

// Turning this filter hook off as we actually do need the order line item to
// record that a quantity of the product is sold
// add_filter( 'woocommerce_order_item_quantity', 'wcrs_order_item_quantity', 10, 3);

function wcrs_enqueu_scripts($hook) {
  // Only add to the edit.php admin page.
  // See WP docs.
  // if ('edit.php' !== $hook) {
  //   return;
  // }
  wp_enqueue_script('wcrs_admin_settings', plugins_url("/settings.js", __FILE__));
  wp_enqueue_style('wcrs_admin_styles', plugins_url("/admin.css", __FILE__));
}

add_action('admin_enqueue_scripts', 'wcrs_enqueu_scripts');

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
function wcrs_add_required_inventory_setting() {

	?><div class='options_group'><?php

		woocommerce_wp_text_input( array(
			'id'			  	=> '_required_product',
			'label'				=> __( 'Required product/variation SKU(s)', 'woocommerce' ),
			'desc_tip'		=> 'true',
		  'description'	=> __( 'Enter a comma separated list of product or variation SKU(s) to deplete when a sale is made.', 'woocommerce' ),
			'type' 				=> 'text',
		) );

	?></div><?php

}

add_action( 'woocommerce_product_options_inventory_product_data', 'wcrs_add_required_inventory_setting' );

/**
 * Add variable setting.
 *
 * @param $loop
 * @param $variation_data
 * @param $variation
 */
function wcrs_add_variation_required_inventory( $loop, $variation_data, $variation ) {

	$variation = wc_get_product( $variation );
	woocommerce_wp_text_input( array(
		'id'				  => "required_product{$loop}",
		'name'				=> "required_product[{$loop}]",
		'value'				=> $variation->get_meta( '_required_product' ),
		'label'				=> __( 'Required product/variation SKU(s)', 'woocommerce' ),
		'desc_tip'		=> 'true',
		'description'	=> __( 'Enter a comma separated list of product or variation SKU(s) to deplete when a sale is made.', 'woocommerce' ),
		'type' 				=> 'text',
	) );

	woocommerce_wp_hidden_input( array(
    'id'     => "wcrs_required_stock-{$loop}",
    'class'  => "wcrs_required_stock",
		'name'   => "wcrs_required_stock[{$loop}]",
		'value'  => $variation->get_meta('_required_stock'),
	) );

}

add_action( 'woocommerce_variation_options_pricing', 'wcrs_add_variation_required_inventory', 50, 3 );

/**
 * Save the custom fields.
 *
 * @param WC_Product $product
 */
function wcrs_admin_process_product_object( $product ) {

	if ( ! empty( $_POST['_required_product'] ) ) {
		$product->update_meta_data( '_required_product', $_POST['_required_product'] );
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
  error_log('$_POST');
  error_log(print_r($_POST, true));
  // error_log('$variation');
  // error_log(print_r($variation, true));
	if ( ! empty( $_POST['required_product'] ) ) {
		$variation->update_meta_data( '_required_product', $_POST['required_product'][ $i ] );
		$variation->save();
  }

	if ( ! empty( $_POST['wcrs_required_stock-'.$i] ) ) {
		$variation->update_meta_data( '_required_stock', stripslashes($_POST['wcrs_required_stock-'.$i ]) );
		$variation->save();
	}
}

add_action( 'woocommerce_save_product_variation', 'wcrs_save_product_variation', 10, 2 );

/**
 * Get the stock quantity of the product variation by checking the stock quanties of the 
 * required product variations and using the minimum of those. If there are no required
 * variations then simply return the product variation's actual quantity
 */

function wcrs_product_variation_get_stock_quantity($quantity, $variation) {
  if ($variation->get_meta( '_required_stock')) {
    $required_stock_settings = json_decode($variation->get_meta('_required_stock'));
    if ( $required_stock_settings->enabled) {
      error_log('$required_stock_settings');
      error_log('------------------------------------------');
      error_log(print_r($required_stock_settings, true));
      error_log('------------------------------------------');
    }
  }
  if ($variation->get_meta( '_required_product')) {
    $required_sku_array = array_map('trim', explode(",", $variation->get_meta( '_required_product')));
    if ($required_sku_array) {
      foreach ($required_sku_array as $required_sku) {
        if (!empty($required_sku)) {
          $required_variation = wcrs_get_product_by_sku($required_sku);
          $required_variation_stock_quantity = $required_variation->get_stock_quantity();
          if ( !isset($temp_stock_quantity)) {
            $temp_stock_quantity = $required_variation_stock_quantity;
          } else {
            $temp_stock_quantity = min($temp_stock_quantity, $required_variation_stock_quantity);
          }
        }
      }
      $quantity = $temp_stock_quantity;
    }
  }
  return $quantity;
}

add_action( 'woocommerce_product_variation_get_stock_quantity', 'wcrs_product_variation_get_stock_quantity', 10, 2 );

/**
 * Get the in-stock status of the product variation by checking the in-stock statuses of the 
 * required product variations. If there are no required variations then simply return
 * the product variation's actual in-stock status
 */

function wcrs_variation_is_in_stock($is_in_stock, $variation) {
  if ($variation->get_meta( '_required_product')) {
    $required_sku_array = array_map('trim', explode(",", $variation->get_meta( '_required_product')));
    if ($required_sku_array) {
      foreach ($required_sku_array as $required_sku) {
        if (!empty($required_sku)) {
          $required_variation = wcrs_get_product_by_sku($required_sku);
          $required_variation_stock_status = $required_variation->get_stock_status();
          // stock status values are instock and outofstock
          if ( $required_variation_stock_status == "outofstock") {
            // if any one of the required items is out of stock then the variation that requires them is also considered out of stock
            $is_in_stock = false;
            break;
          }
        }
      }
    }
  }
  return $is_in_stock;
}

add_filter( 'woocommerce_variation_is_in_stock', 'wcrs_variation_is_in_stock', 10, 2 );

/**
 * hook filter: woocommerce_product_variation_get_stock_status
 * 
 * Get the stock status of the product variation by checking the stock statuses of the 
 * required products or variations. If there are no required variations then simply return
 * the product variation's actual stock status
 */

function wcrs_product_variation_get_stock_status( $status, $variation) {
  if ($variation->get_meta( '_required_product')) {
    $required_sku_array = array_map('trim', explode(",", $variation->get_meta( '_required_product')));
    if ($required_sku_array) {
      foreach ($required_sku_array as $required_sku) {
        if (!empty($required_sku)) {
          $required_variation = wcrs_get_product_by_sku($required_sku);
          $required_variation_stock_status = $required_variation->get_stock_status();
          // stock status values are instock and outofstock
          if ( $required_variation_stock_status == "outofstock") {
            // if any one of the required items is out of stock then the variation that requires them is also considered out of stock
            $status = "outofstock";
            break;
          }
        }
      }
    }
  }
  return $status;
}

add_filter( 'woocommerce_product_variation_get_stock_status', 'wcrs_product_variation_get_stock_status', 10, 2 );

/**
 * hook action: wcrs_reduce_order_stock
 * 
 * @param WC_Order $order
 * 
 * This action hook is called after the stock has been reduced for the order
 * being checked out. Note that if the item in the order being checked out 
 * has required products, then the WooCommerce function wc_reduce_stock_levels
 * will not reduce the quantity of that product (i.e. it will reduce the 
 * quantity by 0, as that value will have been returned from the function
 * below). Once the 0-quantity reduction is complete, this function will be
 * called and will reduce the stock for the required products.
 * 
 */

function wcrs_reduce_order_stock($order) {
  $items = $order->get_items();
  foreach ( $items as $item ) {
    $order_product = wc_get_product( $item['product_id'] );
    if ( $order_product->is_type('variable')) {
      $order_product = wc_get_product( $item['variation_id'] );
    }
    if ( $order_product->get_meta( '_required_product') ) {
      $required_sku_array = array_map('trim', explode(",", $order_product->get_meta( '_required_product')));
      $qty = $item->get_quantity();
      foreach ( $required_sku_array as $required_sku ) {
        $required_product = wcrs_get_product_by_sku($required_sku);
        $new_stock = wc_update_product_stock( $required_product, $qty, 'decrease' );
        if ( is_wp_error( $new_stock ) ) {
          $order->add_order_note( sprintf( __('Unable to reduce stock for required SKU %s by quantity %s', 'woocommerce' ), $required_sku, $qty ) );
        } else {
          $order->add_order_note( sprintf( __('Reduced order stock for required SKU %s by quantity %s', 'woocommerce' ), $required_sku, $qty ) );
        }
      }
    }
  }
}

add_filter( 'woocommerce_reduce_order_stock', 'wcrs_reduce_order_stock', 10, 1);

/**
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

add_filter( 'woocommerce_order_item_quantity', 'wcrs_order_item_quantity', 10, 3);

function wcrs_enqueu_scripts($hook) {
  // Only add to the edit.php admin page.
  // See WP docs.
  error_log('$hook');
  error_log(print_r($hook, true));
  // if ('edit.php' !== $hook) {
  //   return;
  // }
  wp_enqueue_script('wcrs_admin_settings', plugins_url("/settings.js", __FILE__));
  wp_enqueue_style('wcrs_admin_styles', plugins_url("/admin.css", __FILE__));
}

add_action('admin_enqueue_scripts', 'wcrs_enqueu_scripts');

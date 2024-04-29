<?php

namespace StockDependenciesForWooCommerceAdmin {

  class Admin
  {

    /* Custom Stock Dependencies */

    /**
     * 
     * @param string $sku
     *
     * Get the product object by SKU
     *
     */

    function get_product_by_sku($sku)
    {
      $product_id = wc_get_product_id_by_sku($sku);
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
     * The ID of the dependency product is not stored with the SKU in the meta
     * table as the product ID *might* change. If we need to get the settings
     * from the meta table then we need to get the product ID for all the
     * dependencies
     * 
     */

    function get_stock_dependency_meta($product)
    {
      if ($product->get_meta('_stock_dependency')) {
        $product_meta = $product->get_meta('_stock_dependency');
        return $this->update_product_data($product_meta);
      } else {
        return false;
      }
    }

    /** 
     * 
     * @param WP_Order_Item $item
     * 
     * Get the stock dependency for the order item, if it exists
     * 
     */

    function get_order_item_stock_dependencies($item)
    {
      if ($item->get_meta('_stock_dependency')) {
        return json_decode($item->get_meta('_stock_dependency'));
      } else {
        return false;
      }
    }

    /** 
     * 
     * @param WP_Order_Item $item
     * 
     * Get the refunded quantity for the order item, if it exists
     * 
     */

    function get_order_item_refunded_qty($item)
    {
      if ($item->get_meta('_stock_dependency_restocked')) {
        return $item->get_meta('_stock_dependency_restocked');
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

    function product_options_inventory_product_data($product)
    {

      global $post;
      if ($post->post_type == "product") {
        $product = wc_get_product($post->ID);
        woocommerce_wp_hidden_input(array(
          'id'     => 'sdwc_product_stock_dependency',
          'class'  => "sdwc_product_stock_dependency",
          'name'   => "sdwc_product_stock_dependency",
          /**
           * Always get this from the meta table, not the transient so
           * will need to add the product ID
           */
          'value'  => $this->get_stock_dependency_meta($product),
        ));
      }
    }

    /**
     *
     * @param int $loop
     * @param $variation_data
     * @param WC_Product $variation
     * 
     * Add the stock dependencies field for the variations' settings page.
     * 
     */

    function add_variation_dependency_inventory($loop, $variation_data, $variation)
    {

      $variation = wc_get_product($variation);
      woocommerce_wp_hidden_input(array(
        'id'     => "sdwc_variation_stock_dependency-{$loop}",
        'class'  => "sdwc_variation_stock_dependency",
        'name'   => "sdwc_variation_stock_dependency[{$loop}]",
        /**
         * Always get this from the meta table, not the transient so
         * will need to add the product ID
         */
        'value'  => $this->get_stock_dependency_meta($variation),
      ));
    }

    /**
     * 
     * @param string $product_data
     * 
     * Validate that $product_data is proper JSON
     * 
     */

    function validate_product_data($product_data)
    {
      json_decode($product_data);
      return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 
     * @param string $product_data
     * 
     * Update the $product_data to include product ID and validate the SKU
     * 
     */

    function update_product_data($product_data)
    {
      if ($product_data != '') {
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
     *
     * @param WC_Product $product
     * 
     * Save the custom fields.
     * 
     */

    function admin_process_product_object($product)
    {

      if (!empty($_POST['sdwc_product_stock_dependency'])) {
        $product_data = sanitize_text_field(stripslashes($_POST['sdwc_product_stock_dependency']));
        if ($this->validate_product_data($product_data)) {
          /*
           * Save the stock dependency data in the meta field for the product
           * without the dependency's product ID
           */
          $product->update_meta_data('_stock_dependency', $product_data);
          /*
           * Save the stock dependency data in a transient for the product with
           * the dependency's product ID
           */
          $this->save_dependency_transient($product, $product_data);
          return true;
        } else {
          return false;
        }
      }
    }

    /**
     * 
     * Save custom variable fields.
     *
     * @param int $variation_id
     * @param int $i
     * 
     */

    function save_product_variation($variation_id, $i)
    {
      $variation = wc_get_product($variation_id);
      if (!empty($_POST['sdwc_variation_stock_dependency-' . $i])) {
        $product_data = sanitize_text_field(stripslashes($_POST['sdwc_variation_stock_dependency-' . $i]));
        if ($this->validate_product_data($product_data)) {
          /*
           * Save the stock dependency data in the meta field for the product
           * without the dependency's product ID
           */
          $variation->update_meta_data('_stock_dependency', $product_data);
          $variation->save();
          /*
           * Save the stock dependency data in a transient for the product with
           * the dependency's product ID
           */
          $this->save_dependency_transient($variation, $product_data);
          return true;
        } else {
          return false;
        }
      }
    }

    /** 
     * 
     * @parm WP_Product $product
     * @parm $product_data
     * 
     * Save the stock dependency setttings in a database transient
     * 
     */

    function save_dependency_transient($product, $product_data)
    {
      $transient_id = 'sdwc-product-settings-' . $product->get_id();
      set_transient($transient_id, $this->update_product_data($product_data), 7 * DAY_IN_SECONDS);
    }

    /**
     * 
     * @parm WC_Product $product
     * 
     * Get the stock dependency settings from transient if it exists and from
     * the product meta table if there is no transient
     * 
     */

    function get_stock_dependency_settings($product)
    {

      $transient_id = 'sdwc-product-settings-' . $product->get_id();
      if (false !== ($transient_value = get_transient($transient_id))) {
        /** 
         * If the transient exists then use it
         */
        $stock_dependency_settings = json_decode($transient_value);
      } else if (false !== ($stock_dependency_settings_string = $this->get_stock_dependency_meta($product))) {
        /**
         * Get the stock dependency data from the post meta and create the
         * transient
         */
        $this->save_dependency_transient($product, $stock_dependency_settings_string);
        $stock_dependency_settings = json_decode($stock_dependency_settings_string);
      } else {
        /**
         * If there is no transient and there are no settings meta table, then
         * something is wrong
         */
        return false;
      }
      return $stock_dependency_settings;
    }

    /** 
     * 
     * @param WC_Product $product
     * 
     * Check to see if the product has stock dependency settings
     * 
     */

    function has_stock_dependencies($product)
    {

      if (false !== ($stock_dependency_settings = $this->get_stock_dependency_settings($product))) {
        if ($stock_dependency_settings->enabled) {
          return true;
        }
      }
      return false;
    }

    /**
     * 
     * @param int $quantity
     * @param WC_Product $product
     * 
     * Get the stock quantity of the product/variation by checking the stock
     * quanties of the dependency products/variations and using the minimum of
     * those. If there are no dependency products/variations then simply return
     * the product's/variation's actual quantity
     * 
     */

    function product_get_stock_quantity($quantity, $product)
    {
      if ($this->has_stock_dependencies($product)) {
        $stock_dependency_settings = $this->get_stock_dependency_settings($product);
        foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
          if ($stock_dependency->sku) {
            if (wc_get_product($stock_dependency->product_id)) {
              $dependency_product = wc_get_product($stock_dependency->product_id);
              if ($dependency_product) {
                $dependency_product_available = $dependency_product->get_stock_quantity();
                if (!isset($temp_stock_quantity)) {
                  $temp_stock_quantity = intdiv($dependency_product_available, $stock_dependency->qty);
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
      return $quantity;
    }

    /**
     * 
     * @param bool $is_in_stock
     * @param WC_Product $product
     * 
     * Get the in-stock status of the product/variation by checking the stock
     * levels of the dependency products/variations. If there are no stock
     * dependency settings then simply return the product's/variation's actual
     * in-stock status
     * 
     */

    function product_is_in_stock($is_in_stock, $product)
    {
      if ($product->is_type('variable') && $product->has_child() && !$product->managing_stock()) {
        /**
         *  If the product type is variable, and the product has children (i.e.
         *  variations) and stock is not being managed at the product level
         *  (i.e. it is possibly being managed at the variation level) then
         *  check to see if there are stock dependencies for each of the
         *  variations that affect the stock status
         */
        foreach ($product->get_children() as $key => $variation_id) {
          $variation = wc_get_product($variation_id);
          if ($variation->is_type('variation') && $variation->managing_stock()) {
            // $variation_check = $variation->is_in_stock();
            if ($variation->is_in_stock() || $variation->backorders_allowed()) {
              /** if there is at least one variation that has stock then we will
               *  consider the variable product to be instock
               */
              $is_in_stock = true;
              break;
            }
          }
        }
        /**
         *  Updated the stock_status value for the variable product as sometimes
         *  the stock_status in the DB gets out of sync e.g. when any of the
         *  products or variations on which this product depends has had its
         *  stock depleted
         */
        if ($is_in_stock) {
          $product->set_stock_status('instock');
        } else {
          $product->set_stock_status('outofstock');
        }
      } else if (($product->is_type('simple') || $product->is_type('variation')) && $product->managing_stock()) {
        /**
         * if the product is either a simple product or a product variation then
         * and inventory is being managed then check if there are stock
         * dependencies that affect the stock status
         */
        if ($this->has_stock_dependencies($product)) {
          $stock_dependency_settings = $this->get_stock_dependency_settings($product);
          $dependency_is_in_stock = true;
          // product has stock dependencies so check each dependency to see if
          // in stock
          foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
            if ($stock_dependency->product_id) {
              if (wc_get_product($stock_dependency->product_id)) {
                $dependency_product = wc_get_product($stock_dependency->product_id);
                $dependency_product_available = $dependency_product->get_stock_quantity();
                if (intdiv($dependency_product_available, $stock_dependency->qty) <= 0 && !$dependency_product->backorders_allowed()) {
                  $dependency_is_in_stock = false;
                  /**
                   * if there is at least one dependency that is not in stock
                   * then we will consider the product or variation to be
                   * outofstock
                   */
                }
              } else {
                $dependency_is_in_stock = false;
                /** if we cannot get the product or variation dependency by SKU
                 *  then we will consider the product or variation to be
                 *  outofstock
                 */
              }
            }
          }
          $is_in_stock = $dependency_is_in_stock;
        }
        /** updated the stock_status value for the variable product as sometimes
         *  the stock_status in the DB gets out of sync e.g. when any of the
         *  products or variations on which this product depends has had its
         *  stock depleted
         */
        if ($is_in_stock) {
          $product->set_stock_status('instock');
        } else {
          $product->set_stock_status('outofstock');
        }
      }
      return $is_in_stock;
    }

    /**
     * 
     * @param string $status
     * @param WC_Product $product
     * 
     * hook filter: woocommerce_product_variation_get_stock_status
     * 
     * Get the stock status of the product/variation by checking the stock
     * statuses of the dependency products/variations. If there are no
     * dependency products/variations then simply return the
     * product's/variation's actual stock status
     * 
     */

    public function product_get_stock_status($status, $product)
    {
      if ($this->has_stock_dependencies($product)) {
        $stock_dependency_settings = $this->get_stock_dependency_settings($product);
        foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
          if ($stock_dependency->product_id) {
            if (wc_get_product($stock_dependency->product_id)) {
              $dependency_product = wc_get_product($stock_dependency->product_id);
              $dependency_product_available = $dependency_product->get_stock_quantity();
              if (intdiv($dependency_product_available, $stock_dependency->qty) <= 0) {
                if ($dependency_product->backorders_allowed()) {
                  $status = "onbackorder";
                } else {
                  $status = "outofstock";
                }
              }
            }
          } else {
            $status = "outofstock";
            break;
          }
        }
      }
      return $status;
    }

    /**
     * hook action: reduce_order_stock
     * 
     * @param WC_Order $order
     * 
     * This action hook is called after the stock has been reduced for the order
     * being checked out. This function will reduce the inventory of the
     * dependency stock items by the number of items in the order times the
     * number of dependency stock for that item. Note that if the item in the
     * order being checked out has dependency products, then the WooCommerce
     * function wc_reduce_order_stock will set the inventory quantity of that
     * product to zero (0).
     * 
     */

    function reduce_order_stock($order)
    {
      $items = $order->get_items();
      // check each order item to see if there is stock dependency settings
      foreach ($items as $item) {
        // check if the stock dependencies have already been reduced for the
        // order item
        if (!$item->meta_exists('_stock_dependency_reduced')) {
          // the stock dependencies have not yet been reduced for the order
          // item so we need to do that now
          $order_product = wc_get_product($item['product_id']);
          if ($order_product->is_type('variable')) {
            $order_product = wc_get_product($item['variation_id']);
          }
          if ($this->has_stock_dependencies($order_product)) {
            $stock_dependency_settings = $this->get_stock_dependency_settings($order_product);
            $order_item_qty = $item->get_quantity();
            // for each stock dependency sku, decrease the stock by the correct
            // amount and create a note on the order
            foreach ($stock_dependency_settings->stock_dependency as $stock_dependency) {
              if ($stock_dependency->product_id) {
                $dependency_product = wc_get_product($stock_dependency->product_id);
                $old_stock_quantity = $dependency_product->get_stock_quantity();
                $new_stock = wc_update_product_stock(
                  $dependency_product,
                  $order_item_qty * $stock_dependency->qty,
                  'decrease'
                );
                if (is_wp_error($new_stock)) {
                  $order->add_order_note(
                    sprintf(
                      __('Unable to reduce stock for dependency SKU %s from %s to %s [-%s]', 'woocommerce'),
                      $dependency_product->get_sku(),
                      $old_stock_quantity,
                      $old_stock_quantity - ($order_item_qty * $stock_dependency->qty),
                      $order_item_qty * $stock_dependency->qty
                    )
                  );
                } else {
                  $add_order_item_meta = wc_add_order_item_meta(
                    $item->get_id(),
                    '_stock_dependency_reduced',
                    1,
                    true
                  );
                  $order->add_order_note(
                    sprintf(
                      __('Reduced order stock for dependency SKU %s from %s to %s [-%s]', 'woocommerce'),
                      $dependency_product->get_sku(),
                      $old_stock_quantity,
                      $old_stock_quantity - ($order_item_qty * $stock_dependency->qty),
                      $order_item_qty * $stock_dependency->qty
                    )
                  );
                }
              }
            }
            // reset the ordered item stock level
            $this->reset_product_stock_quantity($order_product, $order);

            // Add the stock dependency settings to the order item so that if a
            // return is processed we will know the stock dependency settings
            // that were used for this order item and not assume that the stock
            // dependency settings have not changed
            if (!$item->meta_exists('_stock_dependency')) {
              $add_order_item_meta = wc_add_order_item_meta(
                $item->get_id(),
                '_stock_dependency',
                json_encode($stock_dependency_settings),
                true
              );
            }
          }
        }
      } // end foreach($items)
    }

    /** 
     * 
     * @param int $order_id
     * @param array $items
     * 
     * When an order is created or edited in admin reduce the stock for any
     * dependencies
     */

    function before_save_order_items($order_id, $items)
    {
      $order = wc_get_order($order_id);
      $this->reduce_order_stock($order);
    }

    /** 
     * 
     * @param int $product_id
     * @param int $old_stock
     * @param int $new_stock
     * @param object $order
     * @param object $product
     * 
     */

    function restock_refunded_item($product_id, $old_stock, $new_stock, $order, $product)
    {
      $items = $order->get_items();
      // check each order item to see if there is stock dependency settings
      foreach ($items as $item) {
        if ($product->get_id() == $item->get_product()->get_id()) {
          // proceed only if the product being restocked matches the product in the order line item
          $order_item_refund_qty = $order->get_qty_refunded_for_item($item->get_id());
          if ($order_item_refund_qty < 0) {
            // proceed only if some of the items were refunded
            if (false === ($order_item_previously_refunded = $this->get_order_item_refunded_qty($item))) {
              $order_item_previously_refunded = 0;
            }
            if ($order_item_refund_qty < $order_item_previously_refunded) {
              // proceed only if the number of items being refunded is more than have previously been refunded
              if (false !== ($item_stock_dependency_settings = $this->get_order_item_stock_dependencies($item))) {
                // proceed only if the item has stock dependency settings
                if ($item_stock_dependency_settings->enabled) {
                  // proceed only if the stock dependency settings were enabled when the order was placed
                  foreach ($item_stock_dependency_settings->stock_dependency as $stock_dependency) {
                    if ($stock_dependency->product_id) {
                      $dependency_product = wc_get_product($stock_dependency->product_id);
                      $old_stock_quantity = $dependency_product->get_stock_quantity();
                      // Note: the order_item_refund_qty will be a negative integer
                      $new_stock = wc_update_product_stock(
                        $dependency_product,
                        -1 * ($order_item_refund_qty - $order_item_previously_refunded) * $stock_dependency->qty,
                        'increase'
                      );
                      if (is_wp_error($new_stock)) {
                        $order->add_order_note(
                          sprintf(
                            __('[Refunded] Unable to restock stock for dependency SKU %s from %s to %s [+%s]', 'woocommerce'),
                            $dependency_product->get_sku(),
                            $old_stock_quantity,
                            $old_stock_quantity + (-1 * ($order_item_refund_qty - $order_item_previously_refunded) * $stock_dependency->qty),
                            ($order_item_refund_qty - $order_item_previously_refunded) * $stock_dependency->qty
                          )
                        );
                      } else {
                        if (!wc_get_order_item_meta($item->get_id(), '_stock_dependency_restocked')) {
                          $add_order_item_meta = wc_add_order_item_meta(
                            $item->get_id(),
                            '_stock_dependency_restocked',
                            $order_item_refund_qty,
                            true
                          );
                        } else {
                          $update_order_item_meta = wc_update_order_item_meta(
                            $item->get_id(),
                            '_stock_dependency_restocked',
                            $order_item_refund_qty
                          );
                        }
                        $order->add_order_note(
                          sprintf(
                            __('[Refunded] Restocked order stock for dependency SKU %s from %s to %s [+%s]', 'woocommerce'),
                            $dependency_product->get_sku(),
                            $old_stock_quantity,
                            $old_stock_quantity + (-1 * ($order_item_refund_qty - $order_item_previously_refunded) * $stock_dependency->qty),
                            -1 * ($order_item_refund_qty - $order_item_previously_refunded) * $stock_dependency->qty
                          )
                        );
                      }
                    }
                  }
                  // reset the ordered item stock level
                  $this->reset_product_stock_quantity($item->get_product(), $order);
                }
              }
            }
          }
        }
      }
    }

    /** 
     * 
     * @param int $order_id
     * 
     * If an order is cancelled then restock the stock dependency items
     * excluding any that have already been restocked due to a refund
     * 
     */

    function restock_cancelled_order($order_id)
    {
      $order = wc_get_order($order_id);
      $order_items = $order->get_items();
      if ($order_items) {
        foreach ($order_items as $item_id => $item) {
          // check if order item has stock dependencies
          if (false !== ($item_stock_dependencies = $this->get_order_item_stock_dependencies($item))) {
            // this item has stock dependencies
            if ($item_stock_dependencies->enabled) {
              // stock dependencies are enabled for this item
              // check if any order item qty has already been refunded and restocked
              if (false === ($order_item_previously_refunded = $this->get_order_item_refunded_qty($item))) {
                $order_item_previously_refunded = 0;
              }
              // Note that order_item_previously_refunded will be zero or a negative number
              $restock_qty = $item->get_quantity() + $order_item_previously_refunded;
              $this->restock_order_item($order, $item, $item_stock_dependencies, $restock_qty);
            }
          }
        }
      }
    }

    /** 
     * 
     * @param WC_Order $order
     * @param WC_Order_Item $order_item
     * @param array $item_stock_dependencies
     * @param int $restock_qty
     * 
     * Restock the order items
     * 
     */

    function restock_order_item($order, $order_item, $item_stock_dependencies, $restock_qty)
    {
      // double check that the stock dependencies are enabled
      if ($item_stock_dependencies->enabled) {
        foreach ($item_stock_dependencies->stock_dependency as $item_stock_dependency) {
          if (false !== (wc_update_product_stock(
            $this->get_product_by_sku($item_stock_dependency->sku),
            $item_stock_dependency->qty * $restock_qty,
            'increase'
          ))) {
            $order->add_order_note(sprintf(
              __('[Cancelled] Restocked order stock for dependency SKU %s [+%s]', 'woocommerce'),
              $item_stock_dependency->sku,
              $item_stock_dependency->qty * $restock_qty
            ));
          } else {
            $order->add_order_note(sprintf(
              __('[Cancelled] Unable to restock stock for dependency SKU %s [+%s]', 'woocommerce'),
              $item_stock_dependency->sku,
              $item_stock_dependency->qty * $restock_qty
            ));
          }
        }
      }
      /**
       *  Note: we don't need to reset the stock quantity when an order is
       *  cancelled as the cancellation flow will take care of that
       */
    }

    /**
     * 
     * @param WC_Product $product
     * @param WC_Order $order
     * 
     * Reset the ordered item stock level to the new value by calculating the
     * stock available for dependencies. note that this value will only appear
     * in the admin site as the stock quantity is always recalculated for the
     * shop.
     * 
     */

    function reset_product_stock_quantity($product, $order)
    {
      $product_sku = $product->get_sku();
      $new_stock = wc_update_product_stock(
        $product,
        $this->product_get_stock_quantity(0, $product),
        'set'
      );
      if (is_wp_error($new_stock)) {
        $order->add_order_note(
          sprintf(
            __('Unable to set stock for SKU %s to %d', 'woocommerce'),
            $product_sku,
            $new_stock
          )
        );
      } else {
        $order->add_order_note(
          sprintf(
            __('Set order stock for SKU %s to %d', 'woocommerce'),
            $product_sku,
            $new_stock
          )
        );
      }
    }

    /**
     * 
     * @param array $args
     * 
     * Hide stock dependencies order item meta data in WP admin
     * 
     */

    function hidden_order_itemmeta($args)
    {
      array_push($args, '_stock_dependency');
      array_push($args, '_stock_dependency_reduced');
      array_push($args, '_stock_dependency_restocked');
      return $args;
    }

    /**
     * 
     * @param int $item_id
     * @param object $item
     * @param object $product
     * 
     * When viewing the order in admin, display any stock dependiencies for each
     * item in the order
     * 
     */

    function display_item_dependencies_in_admin($item_id, $item, $product)
    {
      if ($item->meta_exists('_stock_dependency')) {
        $item_stock_dependencies = $this->get_stock_dependency_meta($item);
        $item_stock_dependency_settings = json_decode($item_stock_dependencies);
        if ($item_stock_dependency_settings->enabled) {
          print('<div class="meta" style="margin-left: 10px;">');
          print('<strong>Stock Dependencies</strong>');
          foreach ($item_stock_dependency_settings->stock_dependency as $item_stock_dependency) {
            $dependency_product = wc_get_product($item_stock_dependency->product_id);
            $dependency_text = '<div class="wc-order-item-sku"><strong>SKU</strong>: <a href="';
            if ($dependency_product->get_type() === 'variation') {
              $dependency_text .= get_edit_post_link($dependency_product->get_parent_id());
            } else {
              $dependency_text .= get_edit_post_link($dependency_product->get_id());
            }
            $dependency_text .= '">' . $item_stock_dependency->sku . '</a>&nbsp;<strong>Qty</strong>: ' . $item_stock_dependency->qty . '</div>';
            print($dependency_text);
          }
          print('</div>');
        }
      }
    }

    /**
     * 
     * @param array $links
     * 
     */

    function action_links($links)
    {
      $links[] = '<a href="https://github.com/kmac420/stock-dependencies-for-woocommerce#stock-dependencies-for-woocommerce-plugin" target="_blank">Documentation</a>';
      $links[] = '<a href="tools.php?page=stock-dependencies-settings">Tools</a>';
      return $links;
    }

    /**
     * 
     * @param string $hook
     * 
     * Enqueue the code and style files only to the edit.php admin page and only
     * if the post type is product
     * 
     */

    public function enqueu_scripts($hook)
    {
      if ($hook == 'post.php') {
        global $post;
        $post_type = get_post_type($post);
        if ($post_type == 'product') {
          wp_enqueue_script('sdwc_admin_settings', plugins_url("/settings.js", __FILE__));
          wp_enqueue_style('sdwc_admin_styles', plugins_url("/admin.css", __FILE__));
        }
      }
    }

    /**
     * 
     * Query the WordPress database to get all the saved Stock Dependencies
     * 
     * */

    function get_all_stock_dependency_settings()
    {

      global $wpdb;

      $meta_values = $wpdb->get_results("
        SELECT post_id, meta_value
        FROM wp_postmeta
        WHERE meta_key = '_stock_dependency';
      ");

      return $meta_values;
    }

    /**
     * 
     * Query the WordPress database to get all the saved Stock Dependencies
     * 
     * */

    function delete_all_stock_dependency_transients()
    {

      global $wpdb;

      $query_results = $wpdb->get_results("
        SELECT count(*) AS num_transients
        FROM $wpdb->options
        WHERE option_name LIKE '_transient_sdwc-product-settings%';
      ");

      $num_transients = $query_results[0]->num_transients;

      echo ("<p>Clearing transients ... ");
      if ($num_transients == 0) {
        echo ("No transients to clear");
      } else {
        $wpdb->query(
          $wpdb->prepare(
            "
              DELETE FROM $wpdb->options
              WHERE option_name LIKE '_transient_sdwc-product-settings%';
            "
          )
        );

        $query_results = $wpdb->get_results(
          "
            SELECT count(*) AS num_transients
            FROM $wpdb->options
            WHERE option_name LIKE '_transient_sdwc-product-settings%';
          "
        );
        echo ("<span style=\"color:green;\">Done!</span>");
      }
      echo ("</p>");
    }

    public function settings_page_html()
    {
?>
      <?php

      $clear_transients = filter_input(INPUT_GET, "clear-transients");
      $check_dependencies = filter_input(INPUT_GET, "check-dependencies");
      $sku = filter_input(INPUT_GET, "sku");

      ?>
      <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <h2>Remove Stock Dependency Plugin DB Transients</h2>
        <p>This plugin uses WordPress transients to store some stock dependency
          settings for each product, in order to improve performance. These will
          be automatically cleaned up by WordPress and recreated by the pluing
          as needed, but if your site is not working correctly you can remove
          the plugin transients. Doing this will not break anything but your
          site might perform slower until the transients are recreated when each
          product is viewed in your store.</p>
        <a class="submit button button-primary" href="tools.php?page=stock-dependencies-settings&clear-transients=true">Clear Plugin Transients</a>
        <?php
        if ($clear_transients) {
          $this->delete_all_stock_dependency_transients();
        }
        ?>
        <h2>Check Stock Dependencies</h2>
        <p>Check the stock dependencies for a product by inputting the product
          SKU and click the "Check" button. The plugin will use the configured
          stock dependencies and will determine the available inventory based
          on the dependencies the same way it is calculated in your store.</p>
        <form action="/wp-admin/tools.php?page=stock-dependencies-settings&check-dependencies=true">
          <label for="sku">Product SKU:</label><br>
          <input type="text" id="sku" name="sku" />
          <input type="hidden" id="page" name="page" value="stock-dependencies-settings">
          <input type="hidden" id="check-dependencies" name="check-dependencies" value="true">
          <p>
            <input type="submit" value="Check" class="submit button button-primary">
          </p>
        </form>
        <?php
        if ($check_dependencies) {
          if ($product = $this->get_product_by_sku($sku)) {
            if ($stock_dependency_settings = $this->get_stock_dependency_settings($product)) {
              echo ("<strong>Product</strong><br />");
              echo ("Product name: <a href=\"/wp-admin/post.php?post=");
              echo ($product->is_type('variation') ? $product->get_parent_id() : $product->get_id());
              echo ("&action=edit\">" . $product->get_name() . "</a><br />");
              echo ("Product SKU: " . $product->get_sku() . "<br />");
              echo ("Dependencies enabled: ");
              echo ($stock_dependency_settings->enabled ? 'true' : 'false');
              echo ("<br />");
              echo ("Calculated inventory: " . $this->product_get_stock_quantity(1, $product) . "<br />");
              foreach ($stock_dependency_settings->stock_dependency as $key => $stock_dependency) {
                echo ("<div style=\"margin:20px;\">");
                echo ("<strong>Dependency #" . $key + 1 . "</strong><br />");
                if ($dependency_product = $this->get_product_by_sku($stock_dependency->sku)) {
                  echo ("Dependency name: <a href=\"/wp-admin/post.php?post=");
                  echo ($dependency_product->is_type('variation') ? $dependency_product->get_parent_id() : $dependency_product->get_id());
                  echo ("&action=edit\">" . $dependency_product->get_name() . "</a><br />");
                  // echo ("Dependency name: " . $dependency_product->get_name() . "<br />");
                  echo ("Dependency SKU: " . $dependency_product->get_sku() . "<br />");
                  // echo ("Dependency ID: " . $dependency_product->get_id() . "<br />");
                  echo ("Dependency quantity: " . $stock_dependency->qty . "<br />");
                  echo ("Dependency inventory: " . $dependency_product->get_stock_quantity() . "<br />");
                }
                echo ("</div>");
              }
            } else {
              echo ("There are no stock dependencies for product ");
              echo ("<a href=\"/wp-admin/post.php?post=");
              echo ($product->is_type('variation') ? $product->get_parent_id() : $product->get_id());
              echo ("&action=edit\">" . $product->get_name() . "</a><br />");
            }
          } else {
            echo ("There is no product with SKU: " . $sku . "<br />");
          }
        }
        ?>
        <?php
        $dependency_settings = $this->get_all_stock_dependency_settings();
        ?>
      </div>
<?php
    }

    function settings_page()
    {
      add_submenu_page(
        'tools.php',
        'Stock Dependencies',
        'Stock Dependencies',
        'manage_options',
        'stock-dependencies-settings',
        array($this, 'settings_page_html')
      );
    }
  }
}

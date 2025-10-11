<?php

namespace StockDependenciesForWooCommerceAdmin {

	class Admin {

		/* Meta Keys */
		const META_STOCK_DEPENDENCY     = '_stock_dependency';
		const META_DEPENDENCY_REDUCED   = '_stock_dependency_reduced';
		const META_DEPENDENCY_RESTOCKED = '_stock_dependency_restocked';

		/* Transient Prefix */
		const TRANSIENT_PREFIX = 'sdwc-product-settings-';

		/* Nonce Actions */
		const NONCE_SAVE_PRODUCT   = 'sdwc_save_product_dependency';
		const NONCE_SAVE_VARIATION = 'sdwc_save_variation_dependency';

		/* Nonce Names */
		const NONCE_PRODUCT_NAME   = 'sdwc_product_nonce';
		const NONCE_VARIATION_NAME = 'sdwc_variation_nonce';

		/* Custom Stock Dependencies */

		/**
		 * Retrieve a WooCommerce product by its SKU
		 *
		 * This method looks up a product using WooCommerce's SKU system. It first
		 * retrieves the product ID and then returns the full product object.
		 *
		 * @param string $sku The product SKU to search for
		 *
		 * @return \WC_Product|false Product object if found, false otherwise
		 */
		function get_product_by_sku( $sku ) {
			$product_id = wc_get_product_id_by_sku( $sku );
			if ( $product_id ) {
				return wc_get_product( $product_id );
			} else {
				return false;
			}
		}

		/**
		 * Retrieve stock dependency metadata for a product
		 *
		 * Gets the stock dependency settings from product metadata. The dependency
		 * product IDs are stored with SKUs (not just IDs) because product IDs may
		 * change during imports/migrations. This method retrieves the raw meta and
		 * updates it with current product IDs via update_product_data().
		 *
		 * @param \WC_Product $product The product or variation object
		 *
		 * @return string|false JSON-encoded dependency settings with product IDs, or false if none exist
		 */
		function get_stock_dependency_meta( $product ) {
			if ( $product->get_meta( self::META_STOCK_DEPENDENCY ) ) {
				$product_meta = $product->get_meta( self::META_STOCK_DEPENDENCY );
				return $this->update_product_data( $product_meta );
			} else {
				return false;
			}
		}

		/**
		 * Retrieve stock dependency settings for an order item
		 *
		 * Fetches the dependency configuration that was saved when the order was placed.
		 * This preserves historical dependency settings even if the product's current
		 * settings have changed.
		 *
		 * @param \WC_Order_Item_Product $item The order line item
		 *
		 * @return object|false Decoded dependency settings object, or false if none exist
		 */
		function get_order_item_stock_dependencies( $item ) {
			if ( $item->get_meta( self::META_STOCK_DEPENDENCY ) ) {
				$meta = $item->get_meta( self::META_STOCK_DEPENDENCY );
				return ! empty( $meta ) ? json_decode( $meta ) : false;
			} else {
				return false;
			}
		}

		/**
		 * Get the previously restocked quantity for an order item
		 *
		 * Tracks how many units have already been restocked due to refunds, preventing
		 * double-restocking if multiple refunds occur for the same item.
		 *
		 * @param \WC_Order_Item_Product $item The order line item
		 *
		 * @return int|false The restocked quantity (negative number), or false if never restocked
		 */
		function get_order_item_refunded_qty( $item ) {
			if ( $item->get_meta( self::META_DEPENDENCY_RESTOCKED ) ) {
				return $item->get_meta( self::META_DEPENDENCY_RESTOCKED );
			} else {
				return false;
			}
		}

		/**
		 *
		 * @param WC_Product $product
		 *
		 * Add the stock dependency field for simple products
		 */
		function product_options_inventory_product_data( $product ) {

			global $post;
			if ( $post->post_type == 'product' ) {
				$product = wc_get_product( $post->ID );
				woocommerce_wp_hidden_input(
					array(
						'id'    => 'sdwc_product_stock_dependency',
						'class' => 'sdwc_product_stock_dependency',
						'name'  => 'sdwc_product_stock_dependency',
						/**
						 * Always get this from the meta table, not the transient so
						 * will need to add the product ID
						 */
						'value' => $this->get_stock_dependency_meta( $product ),
					)
				);
				// Add nonce field for security
				wp_nonce_field( self::NONCE_SAVE_PRODUCT, self::NONCE_PRODUCT_NAME );
			}
		}

		/**
		 *
		 * @param int        $loop
		 * @param $variation_data
		 * @param WC_Product $variation
		 *
		 * Add the stock dependencies field for the variations' settings page.
		 */
		function add_variation_dependency_inventory( $loop, $variation_data, $variation ) {

			$variation = wc_get_product( $variation );
			woocommerce_wp_hidden_input(
				array(
					'id'    => "sdwc_variation_stock_dependency-{$loop}",
					'class' => 'sdwc_variation_stock_dependency',
					'name'  => "sdwc_variation_stock_dependency[{$loop}]",
					/**
					* Always get this from the meta table, not the transient so
					* will need to add the product ID
					*/
					'value' => $this->get_stock_dependency_meta( $variation ),
				)
			);
			// Add nonce field for variations (only once, not for each variation)
			if ( $loop == 0 ) {
				wp_nonce_field( self::NONCE_SAVE_VARIATION, self::NONCE_VARIATION_NAME );
			}
		}

		/**
		 * Validate that product dependency data is valid JSON
		 *
		 * Checks if the provided string can be successfully decoded as JSON,
		 * ensuring data integrity before saving to database.
		 *
		 * @param string $product_data JSON string containing dependency settings
		 *
		 * @return bool True if valid JSON, false otherwise
		 */
		function validate_product_data( $product_data ) {
			if ( empty( $product_data ) ) {
				return false;
			}
			json_decode( $product_data );
			return ( json_last_error() == JSON_ERROR_NONE );
		}

		/**
		 * Synchronize dependency data with current SKUs and product IDs
		 *
		 * This critical method ensures dependency data integrity by:
		 * 1. Adding product IDs for SKU-only dependencies
		 * 2. Validating that product IDs still exist
		 * 3. Updating SKUs if the product's SKU has changed
		 * 4. Removing invalid dependencies (deleted products)
		 * 5. Disabling dependencies if all are invalid
		 *
		 * WHY: Product IDs may change during imports, but SKUs are more stable.
		 * We store both and use this method to keep them synchronized.
		 *
		 * @param string $product_data JSON-encoded dependency settings
		 *
		 * @return string Updated JSON-encoded dependency settings
		 */
		function update_product_data( $product_data ) {
			if ( ! empty( $product_data ) && $product_data != '' ) {
				$meta_updated              = false;
				$stock_dependency_settings = json_decode( $product_data );

				// Defensive check: ensure stock_dependency exists and is iterable
				// This prevents fatal errors if the JSON structure is malformed
				if ( ! isset( $stock_dependency_settings->stock_dependency ) ||
				( ! is_array( $stock_dependency_settings->stock_dependency ) && ! is_object( $stock_dependency_settings->stock_dependency ) ) ) {
					return $product_data;
				}

				// Process each dependency to maintain data integrity
				foreach ( $stock_dependency_settings->stock_dependency as $key => $stock_dependency ) {

					// CASE 1: No product_id yet - this is a new dependency or legacy data
					// We need to lookup the product ID from the SKU for performance
					if ( ! isset( $stock_dependency->product_id ) ) {
						if ( $this->get_product_by_sku( $stock_dependency->sku ) ) {
							$stock_dependency_product     = $this->get_product_by_sku( $stock_dependency->sku );
							$stock_dependency->product_id = $stock_dependency_product->get_id();
						} else {
							// SKU doesn't exist in catalog - remove invalid dependency
							unset( $stock_dependency_settings->stock_dependency[ $key ] );
						}
						$meta_updated = true;
					} else {
						// CASE 2: Have product_id - verify it's still valid and SKU hasn't changed
						$stock_dependency_product = wc_get_product( $stock_dependency->product_id );

						if ( $stock_dependency_product && $stock_dependency_product->get_sku() != $stock_dependency->sku ) {
							// Product exists but SKU changed - update our stored SKU
							$stock_dependency->sku = $stock_dependency_product->get_sku();
							$meta_updated          = true;
						} elseif ( ! $stock_dependency_product ) {
							// Product was deleted - remove this invalid dependency
							unset( $stock_dependency_settings->stock_dependency[ $key ] );
							$meta_updated = true;
						}
					}
				}

				// If all dependencies were removed, disable the feature entirely
				if ( count( $stock_dependency_settings->stock_dependency ) == 0 ) {
					$stock_dependency_settings->enabled = false;
					$meta_updated                       = true;
				}

				// Only re-encode if we made changes to avoid unnecessary writes
				if ( $meta_updated ) {
					$product_data = json_encode( $stock_dependency_settings );
				}
			}
			return $product_data;
		}

		/**
		 *
		 * @param WC_Product $product
		 *
		 * Save the custom fields.
		 */
		function admin_process_product_object( $product ) {
			// Verify nonce for security
			if ( ! isset( $_POST[ self::NONCE_PRODUCT_NAME ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_PRODUCT_NAME ], self::NONCE_SAVE_PRODUCT ) ) {
				return false;
			}

			// Check user capabilities
			if ( ! current_user_can( 'edit_products' ) ) {
				return false;
			}

			if ( ! empty( $_POST['sdwc_product_stock_dependency'] ) ) {
				$product_data = sanitize_text_field( stripslashes( $_POST['sdwc_product_stock_dependency'] ) );
				if ( $this->validate_product_data( $product_data ) ) {
					/*
					* Save the stock dependency data in the meta field for the product
					* without the dependency's product ID
					*/
					$product->update_meta_data( self::META_STOCK_DEPENDENCY, $product_data );
					/*
					* Save the stock dependency data in a transient for the product with
					* the dependency's product ID
					*/
					$this->save_dependency_transient( $product, $product_data );
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
		 */
		function save_product_variation( $variation_id, $i ) {
			// Verify nonce for security (only check once, not for each variation)
			if ( $i == 0 ) {
				if ( ! isset( $_POST[ self::NONCE_VARIATION_NAME ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_VARIATION_NAME ], self::NONCE_SAVE_VARIATION ) ) {
					return false;
				}

				// Check user capabilities
				if ( ! current_user_can( 'edit_products' ) ) {
					return false;
				}
			}

			$variation = wc_get_product( $variation_id );
			if ( ! empty( $_POST[ 'sdwc_variation_stock_dependency-' . $i ] ) ) {
				$product_data = sanitize_text_field( stripslashes( $_POST[ 'sdwc_variation_stock_dependency-' . $i ] ) );
				if ( $this->validate_product_data( $product_data ) ) {
					/*
					* Save the stock dependency data in the meta field for the product
					* without the dependency's product ID
					*/
					$variation->update_meta_data( self::META_STOCK_DEPENDENCY, $product_data );
					$variation->save();
					/*
					* Save the stock dependency data in a transient for the product with
					* the dependency's product ID
					*/
					$this->save_dependency_transient( $variation, $product_data );
					return true;
				} else {
					return false;
				}
			}
		}

		/**
		 * Cache dependency settings in a WordPress transient
		 *
		 * Stores processed dependency data (with product IDs) in a transient for
		 * 7 days to improve performance. This avoids repeated SKU-to-ID lookups.
		 *
		 * @param \WC_Product $product      The product or variation
		 * @param string      $product_data JSON dependency settings to cache
		 *
		 * @return void
		 */
		function save_dependency_transient( $product, $product_data ) {
			$transient_id = self::TRANSIENT_PREFIX . $product->get_id();
			set_transient( $transient_id, $this->update_product_data( $product_data ), 7 * DAY_IN_SECONDS );
		}

		/**
		 * Retrieve dependency settings from cache or database
		 *
		 * Attempts to load from transient cache first for performance. Falls back
		 * to product metadata if cache miss, then creates cache for next time.
		 *
		 * @param \WC_Product $product The product or variation
		 *
		 * @return object|false Decoded dependency settings object, or false if none configured
		 */
		function get_stock_dependency_settings( $product ) {

			$transient_id = self::TRANSIENT_PREFIX . $product->get_id();
			if ( false !== ( $transient_value = get_transient( $transient_id ) ) ) {
				/**
				 * If the transient exists then use it
				 */
				$stock_dependency_settings = $transient_value ? json_decode( $transient_value ) : false;
			} elseif ( false !== ( $stock_dependency_settings_string = $this->get_stock_dependency_meta( $product ) ) ) {
				/**
				 * Get the stock dependency data from the post meta and create the
				 * transient
				 */
				$this->save_dependency_transient( $product, $stock_dependency_settings_string );
				$stock_dependency_settings = $stock_dependency_settings_string ? json_decode( $stock_dependency_settings_string ) : false;
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
		 * Check if a product has active stock dependencies
		 *
		 * Returns true only if dependencies are configured AND enabled.
		 *
		 * @param \WC_Product $product The product or variation to check
		 *
		 * @return bool True if product has enabled dependencies, false otherwise
		 */
		function has_stock_dependencies( $product ) {
			if ( ! $product ) {
				return false;
			}

			if ( false !== ( $stock_dependency_settings = $this->get_stock_dependency_settings( $product ) ) ) {
				if ( is_object( $stock_dependency_settings ) && isset( $stock_dependency_settings->enabled ) && $stock_dependency_settings->enabled ) {
					return true;
				}
			}
			return false;
		}

		/**
		 *
		 * @param int        $quantity
		 * @param WC_Product $product
		 *
		 * Get the stock quantity of the product/variation by checking the stock
		 * quanties of the dependency products/variations and using the minimum of
		 * those. If there are no dependency products/variations then simply return
		 * the product's/variation's actual quantity
		 */
		function product_get_stock_quantity( $quantity, $product ) {
			if ( $this->has_stock_dependencies( $product ) ) {
				$stock_dependency_settings = $this->get_stock_dependency_settings( $product );
				// Defensive check: ensure stock_dependency exists and is iterable
				if ( ! isset( $stock_dependency_settings->stock_dependency ) ||
				( ! is_array( $stock_dependency_settings->stock_dependency ) && ! is_object( $stock_dependency_settings->stock_dependency ) ) ) {
					return $quantity;
				}
				foreach ( $stock_dependency_settings->stock_dependency as $stock_dependency ) {
					if ( $stock_dependency->sku ) {
						$dependency_product = wc_get_product( $stock_dependency->product_id );
						if ( $dependency_product ) {
							$dependency_product_available = $dependency_product->get_stock_quantity();
							// Prevent divide-by-zero error
							if ( $stock_dependency->qty <= 0 ) {
								$temp_stock_quantity = 0;
								break;
							}
							if ( ! isset( $temp_stock_quantity ) ) {
								$temp_stock_quantity = intdiv( $dependency_product_available, $stock_dependency->qty );
							} else {
								$temp_stock_quantity = min( $temp_stock_quantity, intdiv( $dependency_product_available, $stock_dependency->qty ) );
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
		 * @param bool       $is_in_stock
		 * @param WC_Product $product
		 *
		 * Get the in-stock status of the product/variation by checking the stock
		 * levels of the dependency products/variations. If there are no stock
		 * dependency settings then simply return the product's/variation's actual
		 * in-stock status
		 */
		function product_is_in_stock( $is_in_stock, $product ) {
			if ( $product->is_type( 'variable' ) && $product->has_child() && ! $product->managing_stock() ) {
				/**
				 *  If the product type is variable, and the product has children (i.e.
				 *  variations) and stock is not being managed at the product level
				 *  (i.e. it is possibly being managed at the variation level) then
				 *  check to see if there are stock dependencies for each of the
				 *  variations that affect the stock status
				 */
				foreach ( $product->get_children() as $key => $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation->is_type( 'variation' ) && $variation->managing_stock() ) {
						// $variation_check = $variation->is_in_stock();
						if ( $variation->is_in_stock() || $variation->backorders_allowed() ) {
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
				if ( $is_in_stock ) {
					$product->set_stock_status( 'instock' );
				} else {
					$product->set_stock_status( 'outofstock' );
				}
			} elseif ( ( $product->is_type( 'simple' ) || $product->is_type( 'variation' ) ) && $product->managing_stock() ) {
				/**
				 * if the product is either a simple product or a product variation then
				 * and inventory is being managed then check if there are stock
				 * dependencies that affect the stock status
				 */
				if ( $this->has_stock_dependencies( $product ) ) {
					$stock_dependency_settings = $this->get_stock_dependency_settings( $product );
					$dependency_is_in_stock    = true;
					// Defensive check: ensure stock_dependency exists and is iterable
					if ( ! isset( $stock_dependency_settings->stock_dependency ) ||
					( ! is_array( $stock_dependency_settings->stock_dependency ) && ! is_object( $stock_dependency_settings->stock_dependency ) ) ) {
						return $is_in_stock;
					}
					// product has stock dependencies so check each dependency to see if
					// in stock
					foreach ( $stock_dependency_settings->stock_dependency as $stock_dependency ) {
						if ( $stock_dependency->product_id ) {
							$dependency_product = wc_get_product( $stock_dependency->product_id );
							if ( $dependency_product ) {
								$dependency_product_available = $dependency_product->get_stock_quantity();
								// Prevent divide-by-zero error
								if ( $stock_dependency->qty <= 0 ) {
										$dependency_is_in_stock = false;
								} elseif ( intdiv( $dependency_product_available, $stock_dependency->qty ) <= 0 && ! $dependency_product->backorders_allowed() ) {
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
				if ( $is_in_stock ) {
					$product->set_stock_status( 'instock' );
				} else {
					$product->set_stock_status( 'outofstock' );
				}
			}
			return $is_in_stock;
		}

		/**
		 *
		 * @param string     $status
		 * @param WC_Product $product
		 *
		 * hook filter: woocommerce_product_variation_get_stock_status
		 *
		 * Get the stock status of the product/variation by checking the stock
		 * statuses of the dependency products/variations. If there are no
		 * dependency products/variations then simply return the
		 * product's/variation's actual stock status
		 */
		public function product_get_stock_status( $status, $product ) {
			if ( $this->has_stock_dependencies( $product ) ) {
				$stock_dependency_settings = $this->get_stock_dependency_settings( $product );
				// Defensive check: ensure stock_dependency exists and is iterable
				if ( ! isset( $stock_dependency_settings->stock_dependency ) ||
				( ! is_array( $stock_dependency_settings->stock_dependency ) && ! is_object( $stock_dependency_settings->stock_dependency ) ) ) {
					return $status;
				}
				foreach ( $stock_dependency_settings->stock_dependency as $stock_dependency ) {
					if ( $stock_dependency->product_id ) {
						$dependency_product = wc_get_product( $stock_dependency->product_id );
						if ( $dependency_product ) {
							$dependency_product_available = $dependency_product->get_stock_quantity();
							// Prevent divide-by-zero error
							if ( $stock_dependency->qty <= 0 ) {
								$status = 'outofstock';
							} elseif ( intdiv( $dependency_product_available, $stock_dependency->qty ) <= 0 ) {
								if ( $dependency_product->backorders_allowed() ) {
									$status = 'onbackorder';
								} else {
									$status = 'outofstock';
								}
							}
						}
					} else {
						$status = 'outofstock';
						break;
					}
				}
			}
			return $status;
		}

		/**
		 * Reduce stock for dependency products when an order is placed
		 *
		 * WooCommerce Hook: woocommerce_reduce_order_stock
		 *
		 * When an order is placed, WooCommerce reduces stock for the ordered items.
		 * This hook then reduces stock for the DEPENDENCY products. For example:
		 * - Customer orders 1x "Gift Basket" (depends on 2x "Chocolate" and 3x "Wine")
		 * - WooCommerce reduces "Gift Basket" stock by 1
		 * - This method reduces "Chocolate" stock by 2 and "Wine" stock by 3
		 *
		 * The dependency settings are saved to order metadata to preserve historical
		 * data for refunds (in case dependencies change after order placement).
		 *
		 * @param \WC_Order $order The order being processed
		 *
		 * @return void
		 */
		function reduce_order_stock( $order ) {
			$items = $order->get_items();
			// check each order item to see if there is stock dependency settings
			foreach ( $items as $item ) {
				// check if the stock dependencies have already been reduced for the
				// order item
				if ( ! $item->meta_exists( self::META_DEPENDENCY_REDUCED ) ) {
					// the stock dependencies have not yet been reduced for the order
					// item so we need to do that now
					$order_product = wc_get_product( $item['product_id'] );
					if ( ! $order_product ) {
						continue; // Skip if product doesn't exist
					}
					if ( $order_product->is_type( 'variable' ) ) {
						$order_product = wc_get_product( $item['variation_id'] );
						if ( ! $order_product ) {
							continue; // Skip if variation doesn't exist
						}
					}
					if ( $this->has_stock_dependencies( $order_product ) ) {
							$stock_dependency_settings = $this->get_stock_dependency_settings( $order_product );
							$order_item_qty            = $item->get_quantity();
							// Defensive check: ensure stock_dependency exists and is iterable
						if ( ! isset( $stock_dependency_settings->stock_dependency ) ||
						( ! is_array( $stock_dependency_settings->stock_dependency ) && ! is_object( $stock_dependency_settings->stock_dependency ) ) ) {
							continue;
						}
						// for each stock dependency sku, decrease the stock by the correct
						// amount and create a note on the order
						foreach ( $stock_dependency_settings->stock_dependency as $stock_dependency ) {
							if ( $stock_dependency->product_id ) {
								$dependency_product = wc_get_product( $stock_dependency->product_id );
								if ( ! $dependency_product ) {
									$order->add_order_note(
										sprintf(
											__( 'Unable to reduce stock for dependency product ID %s - product not found', 'woocommerce' ),
											$stock_dependency->product_id
										)
									);
									continue;
								}
								$old_stock_quantity = $dependency_product->get_stock_quantity();
								$new_stock          = wc_update_product_stock(
									$dependency_product,
									$order_item_qty * $stock_dependency->qty,
									'decrease'
								);
								if ( is_wp_error( $new_stock ) ) {
											$order->add_order_note(
												sprintf(
													__( 'Unable to reduce stock for dependency SKU %1$s from %2$s to %3$s [-%4$s]', 'woocommerce' ),
													$dependency_product->get_sku(),
													$old_stock_quantity,
													$old_stock_quantity - ( $order_item_qty * $stock_dependency->qty ),
													$order_item_qty * $stock_dependency->qty
												)
											);
								} else {
									$add_order_item_meta = wc_add_order_item_meta(
										$item->get_id(),
										self::META_DEPENDENCY_REDUCED,
										1,
										true
									);
									$order->add_order_note(
										sprintf(
											__( 'Reduced order stock for dependency SKU %1$s from %2$s to %3$s [-%4$s]', 'woocommerce' ),
											$dependency_product->get_sku(),
											$old_stock_quantity,
											$old_stock_quantity - ( $order_item_qty * $stock_dependency->qty ),
											$order_item_qty * $stock_dependency->qty
										)
									);
								}
							}
						}
						// reset the ordered item stock level
						$this->reset_product_stock_quantity( $order_product, $order );

						// Add the stock dependency settings to the order item so that if a
						// return is processed we will know the stock dependency settings
						// that were used for this order item and not assume that the stock
						// dependency settings have not changed
						if ( ! $item->meta_exists( self::META_STOCK_DEPENDENCY ) ) {
							$add_order_item_meta = wc_add_order_item_meta(
								$item->get_id(),
								self::META_STOCK_DEPENDENCY,
								json_encode( $stock_dependency_settings ),
								true
							);
						}
					}
				}
			} // end foreach($items)
		}

		/**
		 *
		 * @param int   $order_id
		 * @param array $items
		 *
		 * When an order is created or edited in admin reduce the stock for any
		 * dependencies
		 */
		function before_save_order_items( $order_id, $items ) {
			$order = wc_get_order( $order_id );
			$this->reduce_order_stock( $order );
		}

		/**
		 *
		 * @param int    $product_id
		 * @param int    $old_stock
		 * @param int    $new_stock
		 * @param object $order
		 * @param object $product
		 */
		function restock_refunded_item( $product_id, $old_stock, $new_stock, $order, $product ) {
			$items = $order->get_items();
			// check each order item to see if there is stock dependency settings
			foreach ( $items as $item ) {
				if ( $product->get_id() == $item->get_product()->get_id() ) {
					// proceed only if the product being restocked matches the product in the order line item
					$order_item_refund_qty = $order->get_qty_refunded_for_item( $item->get_id() );
					if ( $order_item_refund_qty < 0 ) {
						// proceed only if some of the items were refunded
						if ( false === ( $order_item_previously_refunded = $this->get_order_item_refunded_qty( $item ) ) ) {
								$order_item_previously_refunded = 0;
						}
						if ( $order_item_refund_qty < $order_item_previously_refunded ) {
							// proceed only if the number of items being refunded is more than have previously been refunded
							if ( false !== ( $item_stock_dependency_settings = $this->get_order_item_stock_dependencies( $item ) ) ) {
								// proceed only if the item has stock dependency settings
								if ( $item_stock_dependency_settings->enabled ) {
									// proceed only if the stock dependency settings were enabled when the order was placed
									foreach ( $item_stock_dependency_settings->stock_dependency as $stock_dependency ) {
										if ( $stock_dependency->product_id ) {
											$dependency_product = wc_get_product( $stock_dependency->product_id );
											if ( ! $dependency_product ) {
												$order->add_order_note(
													sprintf(
														__( '[Refunded] Unable to restock for dependency product ID %s - product not found', 'woocommerce' ),
														$stock_dependency->product_id
													)
												);
												continue;
											}
											$old_stock_quantity = $dependency_product->get_stock_quantity();
											// Note: the order_item_refund_qty will be a negative integer
											$new_stock = wc_update_product_stock(
												$dependency_product,
												-1 * ( $order_item_refund_qty - $order_item_previously_refunded ) * $stock_dependency->qty,
												'increase'
											);
											if ( is_wp_error( $new_stock ) ) {
														$order->add_order_note(
															sprintf(
																__( '[Refunded] Unable to restock stock for dependency SKU %1$s from %2$s to %3$s [+%4$s]', 'woocommerce' ),
																$dependency_product->get_sku(),
																$old_stock_quantity,
																$old_stock_quantity + ( -1 * ( $order_item_refund_qty - $order_item_previously_refunded ) * $stock_dependency->qty ),
																( $order_item_refund_qty - $order_item_previously_refunded ) * $stock_dependency->qty
															)
														);
											} else {
												if ( ! wc_get_order_item_meta( $item->get_id(), self::META_DEPENDENCY_RESTOCKED ) ) {
														$add_order_item_meta = wc_add_order_item_meta(
															$item->get_id(),
															self::META_DEPENDENCY_RESTOCKED,
															$order_item_refund_qty,
															true
														);
												} else {
													$update_order_item_meta = wc_update_order_item_meta(
														$item->get_id(),
														self::META_DEPENDENCY_RESTOCKED,
														$order_item_refund_qty
													);
												}
												$order->add_order_note(
													sprintf(
														__( '[Refunded] Restocked order stock for dependency SKU %1$s from %2$s to %3$s [+%4$s]', 'woocommerce' ),
														$dependency_product->get_sku(),
														$old_stock_quantity,
														$old_stock_quantity + ( -1 * ( $order_item_refund_qty - $order_item_previously_refunded ) * $stock_dependency->qty ),
														-1 * ( $order_item_refund_qty - $order_item_previously_refunded ) * $stock_dependency->qty
													)
												);
											}
										}
									}
									// reset the ordered item stock level
									$this->reset_product_stock_quantity( $item->get_product(), $order );
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
		 */
		function restock_cancelled_order( $order_id ) {
			$order       = wc_get_order( $order_id );
			$order_items = $order->get_items();
			if ( $order_items ) {
				foreach ( $order_items as $item_id => $item ) {
					// check if order item has stock dependencies
					if ( false !== ( $item_stock_dependencies = $this->get_order_item_stock_dependencies( $item ) ) ) {
						// this item has stock dependencies
						if ( $item_stock_dependencies->enabled ) {
								// stock dependencies are enabled for this item
								// check if any order item qty has already been refunded and restocked
							if ( false === ( $order_item_previously_refunded = $this->get_order_item_refunded_qty( $item ) ) ) {
								$order_item_previously_refunded = 0;
							}
							// Note that order_item_previously_refunded will be zero or a negative number
							$restock_qty = $item->get_quantity() + $order_item_previously_refunded;
							$this->restock_order_item( $order, $item, $item_stock_dependencies, $restock_qty );
						}
					}
				}
			}
		}

		/**
		 *
		 * @param WC_Order      $order
		 * @param WC_Order_Item $order_item
		 * @param array         $item_stock_dependencies
		 * @param int           $restock_qty
		 *
		 * Restock the order items
		 */
		function restock_order_item( $order, $order_item, $item_stock_dependencies, $restock_qty ) {
			// double check that the stock dependencies are enabled and is an object
			if ( is_object( $item_stock_dependencies ) && isset( $item_stock_dependencies->enabled ) && $item_stock_dependencies->enabled ) {
				// Make sure stock_dependency exists and is something we can iterate over
				if (
				isset( $item_stock_dependencies->stock_dependency ) &&
				( is_array( $item_stock_dependencies->stock_dependency ) || is_object( $item_stock_dependencies->stock_dependency ) )
				) {
					foreach ( $item_stock_dependencies->stock_dependency as $item_stock_dependency ) {
						if ( false !== ( wc_update_product_stock(
							$this->get_product_by_sku( $item_stock_dependency->sku ),
							$item_stock_dependency->qty * $restock_qty,
							'increase'
						) ) ) {
							$order->add_order_note(
								sprintf(
									__( '[Cancelled] Restocked order stock for dependency SKU %1$s [+%2$s]', 'woocommerce' ),
									$item_stock_dependency->sku,
									$item_stock_dependency->qty * $restock_qty
								)
							);
						} else {
							$order->add_order_note(
								sprintf(
									__( '[Cancelled] Unable to restock stock for dependency SKU %1$s [+%2$s]', 'woocommerce' ),
									$item_stock_dependency->sku,
									$item_stock_dependency->qty * $restock_qty
								)
							);
						}
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
		 * @param WC_Order   $order
		 *
		 * Reset the ordered item stock level to the new value by calculating the
		 * stock available for dependencies. note that this value will only appear
		 * in the admin site as the stock quantity is always recalculated for the
		 * shop.
		 */
		function reset_product_stock_quantity( $product, $order ) {
			$product_sku = $product->get_sku();
			$new_stock   = wc_update_product_stock(
				$product,
				$this->product_get_stock_quantity( 0, $product ),
				'set'
			);
			if ( is_wp_error( $new_stock ) ) {
				$order->add_order_note(
					sprintf(
						__( 'Unable to set stock for SKU %1$s to %2$d', 'woocommerce' ),
						$product_sku,
						$new_stock
					)
				);
			} else {
				$order->add_order_note(
					sprintf(
						__( 'Set order stock for SKU %1$s to %2$d', 'woocommerce' ),
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
		 */
		function hidden_order_itemmeta( $args ) {
			array_push( $args, self::META_STOCK_DEPENDENCY );
			array_push( $args, self::META_DEPENDENCY_REDUCED );
			array_push( $args, self::META_DEPENDENCY_RESTOCKED );
			return $args;
		}

		/**
		 *
		 * @param int    $item_id
		 * @param object $item
		 * @param object $product
		 *
		 * When viewing the order in admin, display any stock dependiencies for each
		 * item in the order
		 */
		function display_item_dependencies_in_admin( $item_id, $item, $product ) {
			if ( $item->meta_exists( self::META_STOCK_DEPENDENCY ) ) {
				$item_stock_dependencies = $this->get_stock_dependency_meta( $item );
				if ( ! empty( $item_stock_dependencies ) ) {
					$item_stock_dependency_settings = json_decode( $item_stock_dependencies );
					if ( is_object( $item_stock_dependency_settings ) && isset( $item_stock_dependency_settings->enabled ) && $item_stock_dependency_settings->enabled ) {
						print( '<div class="meta" style="margin-left: 10px;">' );
						print( '<strong>Stock Dependencies</strong>' );
						foreach ( $item_stock_dependency_settings->stock_dependency as $item_stock_dependency ) {
								$dependency_product = wc_get_product( $item_stock_dependency->product_id );
							if ( ! $dependency_product ) {
								// Product doesn't exist anymore, just show SKU
								$dependency_text = '<div class="wc-order-item-sku"><strong>SKU</strong>: ' . $item_stock_dependency->sku . ' (product not found)&nbsp;<strong>Qty</strong>: ' . $item_stock_dependency->qty . '</div>';
								print( $dependency_text );
								continue;
							}
							$dependency_text = '<div class="wc-order-item-sku"><strong>SKU</strong>: <a href="';
							if ( $dependency_product->get_type() === 'variation' ) {
								$dependency_text .= get_edit_post_link( $dependency_product->get_parent_id() );
							} else {
								$dependency_text .= get_edit_post_link( $dependency_product->get_id() );
							}
							$dependency_text .= '">' . $item_stock_dependency->sku . '</a>&nbsp;<strong>Qty</strong>: ' . $item_stock_dependency->qty . '</div>';
							print( $dependency_text );
						}
						print( '</div>' );
					}
				}
			}
		}

		/**
		 *
		 * @param array $links
		 */
		function action_links( $links ) {
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
		 */
		public function enqueu_scripts( $hook ) {
			if ( $hook == 'post.php' ) {
				global $post;
				$post_type = get_post_type( $post );
				if ( $post_type == 'product' ) {
					wp_enqueue_script( 'sdwc_admin_settings', plugins_url( '/settings.js', __FILE__ ) );
					wp_enqueue_style( 'sdwc_admin_styles', plugins_url( '/admin.css', __FILE__ ) );
				}
			}
		}

		/**
		 * Retrieve all products with stock dependencies from database
		 *
		 * Queries the postmeta table for all products that have dependency settings.
		 * Supports pagination for sites with thousands of products to avoid memory issues.
		 *
		 * @param int $limit  Maximum number of results to return (default: -1 for all results)
		 * @param int $offset Number of results to skip for pagination (default: 0)
		 *
		 * @return array Array of objects with post_id and meta_value properties
		 */
		function get_all_stock_dependency_settings( $limit = -1, $offset = 0 ) {

			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				self::META_STOCK_DEPENDENCY
			);

			// Add pagination if limit is specified
			if ( $limit > 0 ) {
				$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
			}

			$meta_values = $wpdb->get_results( $query );

			return $meta_values;
		}

		/**
		 *
		 * Query the WordPress database to get all the saved Stock Dependencies
		 * */
		function delete_all_stock_dependency_transients() {

			global $wpdb;

			$query_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT count(*) AS num_transients FROM {$wpdb->options} WHERE option_name LIKE %s",
					'_transient_' . self::TRANSIENT_PREFIX . '%'
				)
			);

			$num_transients = $query_results[0]->num_transients;

			echo ( '<p>Clearing transients ... ' );
			if ( $num_transients == 0 ) {
				echo ( 'No transients to clear' );
			} else {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
						'_transient_' . self::TRANSIENT_PREFIX . '%'
					)
				);

				$query_results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT count(*) AS num_transients FROM {$wpdb->options} WHERE option_name LIKE %s",
						'_transient_' . self::TRANSIENT_PREFIX . '%'
					)
				);
				echo ( '<span style="color:green;">Done!</span>' );
			}
			echo ( '</p>' );
		}

		public function settings_page_html() {
			?>
			<?php

			$clear_transients   = filter_input( INPUT_GET, 'clear-transients' );
			$check_dependencies = filter_input( INPUT_GET, 'check-dependencies' );
			$sku                = filter_input( INPUT_GET, 'sku' );

			?>
		<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
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
			if ( $clear_transients ) {
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
			if ( $check_dependencies ) {
				if ( $product = $this->get_product_by_sku( $sku ) ) {
					if ( $stock_dependency_settings = $this->get_stock_dependency_settings( $product ) ) {
						echo ( '<strong>Product</strong><br />' );
						echo ( 'Product name: <a href="/wp-admin/post.php?post=' );
						echo ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );
						echo ( '&action=edit">' . $product->get_name() . '</a><br />' );
						echo ( 'Product SKU: ' . $product->get_sku() . '<br />' );
						echo ( 'Dependencies enabled: ' );
						echo ( $stock_dependency_settings->enabled ? 'true' : 'false' );
						echo ( '<br />' );
						echo ( 'Calculated inventory: ' . $this->product_get_stock_quantity( 1, $product ) . '<br />' );
						foreach ( $stock_dependency_settings->stock_dependency as $key => $stock_dependency ) {
							echo ( '<div style="margin:20px;">' );
							echo ( '<strong>Dependency #' . $key + 1 . '</strong><br />' );
							if ( $dependency_product = $this->get_product_by_sku( $stock_dependency->sku ) ) {
								echo ( 'Dependency name: <a href="/wp-admin/post.php?post=' );
								echo ( $dependency_product->is_type( 'variation' ) ? $dependency_product->get_parent_id() : $dependency_product->get_id() );
								echo ( '&action=edit">' . $dependency_product->get_name() . '</a><br />' );
								// echo ("Dependency name: " . $dependency_product->get_name() . "<br />");
								echo ( 'Dependency SKU: ' . $dependency_product->get_sku() . '<br />' );
								// echo ("Dependency ID: " . $dependency_product->get_id() . "<br />");
								echo ( 'Dependency quantity: ' . $stock_dependency->qty . '<br />' );
								echo ( 'Dependency inventory: ' . $dependency_product->get_stock_quantity() . '<br />' );
							}
							echo ( '</div>' );
						}
					} else {
						echo ( 'There are no stock dependencies for product ' );
						echo ( '<a href="/wp-admin/post.php?post=' );
						echo ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );
						echo ( '&action=edit">' . $product->get_name() . '</a><br />' );
					}
				} else {
					echo ( 'There is no product with SKU: ' . $sku . '<br />' );
				}
			}
			?>
			<?php
			$dependency_settings = $this->get_all_stock_dependency_settings();
			?>
		</div>
			<?php
		}

		function settings_page() {
			add_submenu_page(
				'tools.php',
				'Stock Dependencies',
				'Stock Dependencies',
				'manage_options',
				'stock-dependencies-settings',
				array( $this, 'settings_page_html' )
			);
		}
	}
}

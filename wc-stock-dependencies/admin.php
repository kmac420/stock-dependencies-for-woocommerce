<?php

namespace StockDependenciesForWooCommerceAdmin {

    /**
     * Admin Class
     * 
     * Handles all administrative functionality for the Stock Dependencies plugin.
     * 
     * @since 1.6.3
     */
    class Admin {
        /** @var string */
        private const STOCK_DEPENDENCY_META_KEY = '_stock_dependency';
        
        /** @var string */
        private const TRANSIENT_PREFIX = 'sdwc-product-settings-';

        /**
         * Get product by SKU
         *
         * @param string $sku Product SKU
         * @return \WC_Product|false
         */
        public function get_product_by_sku(string $sku) {
            $product_id = wc_get_product_id_by_sku($sku);
            return $product_id ? wc_get_product($product_id) : false;
        }

        /**
         * Get stock dependency meta
         *
         * @param \WC_Product $product
         * @return string|false
         */
        public function get_stock_dependency_meta($product) {
            $product_meta = $product->get_meta(self::STOCK_DEPENDENCY_META_KEY);
            return $product_meta ? $this->update_product_data($product_meta) : false;
        }

        /**
         * Get order item stock dependencies
         *
         * @param \WC_Order_Item $item
         * @return object|false
         */
        public function get_order_item_stock_dependencies($item) {
            $meta = $item->get_meta(self::STOCK_DEPENDENCY_META_KEY);
            return $meta ? json_decode($meta) : false;
        }

        /**
         * Get order item refunded quantity
         *
         * @param \WC_Order_Item $item
         * @return int|false
         */
        public function get_order_item_refunded_qty($item) {
            $meta = $item->get_meta('_stock_dependency_restocked');
            return $meta !== '' ? (int) $meta : false;
        }

        /**
         * Add inventory field for simple products
         *
         * @param \WC_Product $product
         * @return void
         */
        public function product_options_inventory_product_data($product) {
            global $post;
            
            if ($post->post_type !== "product") {
                return;
            }

            $product = wc_get_product($post->ID);
            woocommerce_wp_hidden_input([
                'id'     => 'sdwc_product_stock_dependency',
                'class'  => "sdwc_product_stock_dependency",
                'name'   => "sdwc_product_stock_dependency",
                'value'  => $this->get_stock_dependency_meta($product),
            ]);
        }

        /**
         * Add variation dependency inventory
         *
         * @param int $loop
         * @param array $variation_data
         * @param \WC_Product_Variation $variation
         * @return void
         */
        public function add_variation_dependency_inventory($loop, $variation_data, $variation) {
            $variation = wc_get_product($variation);
            woocommerce_wp_hidden_input([
                'id'     => "sdwc_variation_stock_dependency-{$loop}",
                'class'  => "sdwc_variation_stock_dependency",
                'name'   => "sdwc_variation_stock_dependency[{$loop}]",
                'value'  => $this->get_stock_dependency_meta($variation),
            ]);
        }

        /**
         * Validate product data
         *
         * @param string $product_data
         * @return bool
         */
        public function validate_product_data(string $product_data): bool {
            json_decode($product_data);
            return (json_last_error() === JSON_ERROR_NONE);
        }

        /**
         * Update product data with product IDs and validate SKUs
         *
         * @param string $product_data
         * @return string
         */
        public function update_product_data(string $product_data): string {
            if (empty($product_data)) {
                return '';
            }

            try {
                $meta_updated = false;
                $stock_dependency_settings = json_decode($product_data);
                
                foreach ($stock_dependency_settings->stock_dependency as $key => $stock_dependency) {
                    if (!isset($stock_dependency->product_id)) {
                        $dependency_product = $this->get_product_by_sku($stock_dependency->sku);
                        if ($dependency_product) {
                            $stock_dependency->product_id = $dependency_product->get_id();
                        } else {
                            unset($stock_dependency_settings->stock_dependency[$key]);
                        }
                        $meta_updated = true;
                    } else {
                        $dependency_product = wc_get_product($stock_dependency->product_id);
                        if ($dependency_product && $dependency_product->get_sku() !== $stock_dependency->sku) {
                            $stock_dependency->sku = $dependency_product->get_sku();
                            $meta_updated = true;
                        }
                    }
                }

                if (count($stock_dependency_settings->stock_dependency) === 0) {
                    $stock_dependency_settings->enabled = false;
                    $meta_updated = true;
                }

                return $meta_updated ? json_encode($stock_dependency_settings) : $product_data;
            } catch (\Exception $e) {
                error_log('Stock Dependencies: Error updating product data - ' . $e->getMessage());
                return $product_data;
            }
        }

        /**
         * Process product object in admin
         *
         * @param \WC_Product $product
         * @return bool
         */
        public function admin_process_product_object($product): bool {
            if (empty($_POST['sdwc_product_stock_dependency'])) {
                return false;
            }

            if (!wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $product->get_id())) {
                return false;
            }

            $product_data = sanitize_text_field(wp_unslash($_POST['sdwc_product_stock_dependency']));
            
            if (!$this->validate_product_data($product_data)) {
                return false;
            }

            $product->update_meta_data(self::STOCK_DEPENDENCY_META_KEY, $product_data);
            $this->save_dependency_transient($product, $product_data);
            
            return true;
        }

        /**
         * Save product variation
         *
         * @param int $variation_id
         * @param int $i
         * @return bool
         */
        public function save_product_variation($variation_id, $i): bool {
            if (empty($_POST['sdwc_variation_stock_dependency-' . $i])) {
                return false;
            }

            $variation = wc_get_product($variation_id);
            if (!$variation) {
                return false;
            }

            $product_data = sanitize_text_field(wp_unslash($_POST['sdwc_variation_stock_dependency-' . $i]));
            
            if (!$this->validate_product_data($product_data)) {
                return false;
            }

            $variation->update_meta_data(self::STOCK_DEPENDENCY_META_KEY, $product_data);
            $variation->save();
            $this->save_dependency_transient($variation, $product_data);
            
            return true;
        }

        /**
         * Save dependency transient
         *
         * @param \WC_Product $product
         * @param string $product_data
         * @return void
         */
        public function save_dependency_transient($product, string $product_data): void {
            $transient_id = self::TRANSIENT_PREFIX . $product->get_id();
            set_transient($transient_id, $this->update_product_data($product_data), 7 * DAY_IN_SECONDS);
        }

        /**
         * Get stock dependency settings
         *
         * @param \WC_Product $product
         * @return object|false
         */
        public function get_stock_dependency_settings($product) {
            $transient_id = self::TRANSIENT_PREFIX . $product->get_id();
            $transient_value = get_transient($transient_id);

            if ($transient_value !== false) {
                return json_decode($transient_value);
            }

            $settings_string = $this->get_stock_dependency_meta($product);
            if ($settings_string !== false) {
                $this->save_dependency_transient($product, $settings_string);
                return json_decode($settings_string);
            }

            return false;
        }

        /**
         * Check if product has stock dependencies
         *
         * @param \WC_Product $product
         * @return bool
         */
        public function has_stock_dependencies($product): bool {
            $settings = $this->get_stock_dependency_settings($product);
            return $settings && $settings->enabled;
        }

        /**
         * Get product stock quantity
         *
         * @param int $quantity
         * @param \WC_Product $product
         * @return int
         */
        public function product_get_stock_quantity($quantity, $product): int {
            if (!$this->has_stock_dependencies($product)) {
                return $quantity;
            }

            try {
                $settings = $this->get_stock_dependency_settings($product);
                $temp_stock_quantity = PHP_INT_MAX; // Initialize with maximum integer value

                foreach ($settings->stock_dependency as $dependency) {
                    if (empty($dependency->sku)) {
                        continue;
                    }

                    $dependency_product = wc_get_product($dependency->product_id);
                    $available = $dependency_product ? $dependency_product->get_stock_quantity() : 0;

                    if (!is_numeric($available)) {
                        return 0;
                    }

                    $current_quantity = intdiv(absint($available), $dependency->qty);
                    $temp_stock_quantity = min($temp_stock_quantity, $current_quantity);
                }

                return $temp_stock_quantity === PHP_INT_MAX ? 0 : $temp_stock_quantity;
            } catch (\Exception $e) {
                error_log('Stock Dependencies: Error calculating stock quantity - ' . $e->getMessage());
                return 0;
            }
        }

        /**
         * Check if product is in stock
         *
         * @param bool $is_in_stock
         * @param \WC_Product $product
         * @return bool
         */
        public function product_is_in_stock($is_in_stock, $product): bool {
            if ($product->is_type('variable') && $product->has_child() && !$product->managing_stock()) {
                foreach ($product->get_children() as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation && $variation->is_type('variation') && $variation->managing_stock()) {
                        if ($variation->is_in_stock() || $variation->backorders_allowed()) {
                            $is_in_stock = true;
                            break;
                        }
                    }
                }

                $product->set_stock_status($is_in_stock ? 'instock' : 'outofstock');
            } elseif (($product->is_type('simple') || $product->is_type('variation')) && $product->managing_stock()) {
                if ($this->has_stock_dependencies($product)) {
                    $settings = $this->get_stock_dependency_settings($product);
                    $dependency_is_in_stock = true;

                    foreach ($settings->stock_dependency as $dependency) {
                        if ($dependency->product_id) {
                            $dependency_product = wc_get_product($dependency->product_id);
                            $available = $dependency_product ? $dependency_product->get_stock_quantity() : 0;

                            if (is_numeric($available)) {
                                if (intdiv(absint($available), $dependency->qty) <= 0 && !$dependency_product->backorders_allowed()) {
                                    $dependency_is_in_stock = false;
                                    break;
                                }
                            } else {
                                $dependency_is_in_stock = false;
                                break;
                            }
                        }
                    }

                    $is_in_stock = $dependency_is_in_stock;
                }

                $product->set_stock_status($is_in_stock ? 'instock' : 'outofstock');
            }

            return $is_in_stock;
        }

        /**
         * Get product stock status
         *
         * @param string $status
         * @param \WC_Product $product
         * @return string
         */
        public function product_get_stock_status($status, $product): string {
            if (!$this->has_stock_dependencies($product)) {
                return $status;
            }

            $settings = $this->get_stock_dependency_settings($product);
            foreach ($settings->stock_dependency as $dependency) {
                if ($dependency->product_id) {
                    $dependency_product = wc_get_product($dependency->product_id);
                    $available = $dependency_product ? $dependency_product->get_stock_quantity() : 0;

                    if (is_numeric($available) && intdiv(absint($available), $dependency->qty) <= 0) {
                        return $dependency_product->backorders_allowed() ? 'onbackorder' : 'outofstock';
                    }
                }
            }

            return 'outofstock';
        }

        /**
         * Reduce order stock
         *
         * @param \WC_Order $order
         * @return void
         */
        public function reduce_order_stock($order): void {
            foreach ($order->get_items() as $item) {
                if ($item->meta_exists('_stock_dependency_reduced')) {
                    continue;
                }

                $product = wc_get_product($item->get_product_id());
                if (!$product) {
                    continue;
                }

                if ($product->is_type('variable')) {
                    $product = wc_get_product($item->get_variation_id());
                }

                if (!$this->has_stock_dependencies($product)) {
                    continue;
                }

                $settings = $this->get_stock_dependency_settings($product);
                $order_item_qty = $item->get_quantity();

                foreach ($settings->stock_dependency as $dependency) {
                    if (!$dependency->product_id) {
                        continue;
                    }

                    $dependency_product = wc_get_product($dependency->product_id);
                    $old_stock = $dependency_product->get_stock_quantity();
                    $reduced_qty = $order_item_qty * $dependency->qty;
                    $new_stock = wc_update_product_stock($dependency_product, $reduced_qty, 'decrease');

                    if (is_wp_error($new_stock)) {
                        $order->add_order_note(sprintf(
                            __('Unable to reduce stock for dependency SKU %s from %s to %s [-%s]', 'woocommerce'),
                            $dependency_product->get_sku(),
                            $old_stock,
                            $old_stock - $reduced_qty,
                            $reduced_qty
                        ));
                    } else {
                        wc_add_order_item_meta($item->get_id(), '_stock_dependency_reduced', 1, true);
                        $order->add_order_note(sprintf(
                            __('Reduced order stock for dependency SKU %s from %s to %s [-%s]', 'woocommerce'),
                            $dependency_product->get_sku(),
                            $old_stock,
                            $old_stock - $reduced_qty,
                            $reduced_qty
                        ));

                        do_action('wcsd_order_item_dependency_stock_reduced', 
                            absint($reduced_qty),
                            absint($old_stock),
                            $dependency_product
                        );
                    }
                }

                $this->reset_product_stock_quantity($product, $order);

                if (!$item->meta_exists('_stock_dependency')) {
                    wc_add_order_item_meta(
                        $item->get_id(),
                        '_stock_dependency',
                        json_encode($settings),
                        true
                    );
                }
            }
        }

        /**
         * Handle before save order items
         *
         * @param int $order_id
         * @param array $items
         * @return void
         */
        public function before_save_order_items($order_id, $items): void {
            $order = wc_get_order($order_id);
            if ($order) {
                $this->reduce_order_stock($order);
            }
        }

        /**
         * Restock refunded item
         *
         * @param int $product_id
         * @param int $old_stock
         * @param int $new_stock
         * @param \WC_Order $order
         * @param \WC_Product $product
         * @return void
         */
        public function restock_refunded_item($product_id, $old_stock, $new_stock, $order, $product): void {
            foreach ($order->get_items() as $item) {
                if ($product->get_id() !== wc_get_product($item->get_product_id())->get_id()) {
                    continue;
                }

                $refund_qty = $order->get_qty_refunded_for_item($item->get_id());
                if ($refund_qty >= 0) {
                    continue;
                }

                $previously_refunded = $this->get_order_item_refunded_qty($item) ?: 0;
                if ($refund_qty >= $previously_refunded) {
                    continue;
                }

                $dependencies = $this->get_order_item_stock_dependencies($item);
                if (!$dependencies || !$dependencies->enabled) {
                    continue;
                }

                foreach ($dependencies->stock_dependency as $dependency) {
                    if (!$dependency->product_id) {
                        continue;
                    }

                    $dependency_product = wc_get_product($dependency->product_id);
                    $old_qty = $dependency_product->get_stock_quantity();
                    $restock_qty = ($refund_qty - $previously_refunded) * $dependency->qty;
                    $new_qty = wc_update_product_stock(
                        $dependency_product,
                        -1 * $restock_qty,
                        'increase'
                    );

                    if (is_wp_error($new_qty)) {
                        $order->add_order_note(sprintf(
                            __('[Refunded] Unable to restock stock for dependency SKU %s from %s to %s [+%s]', 'woocommerce'),
                            $dependency_product->get_sku(),
                            $old_qty,
                            $old_qty + (-1 * $restock_qty),
                            $restock_qty
                        ));
                    } else {
                        if (!wc_get_order_item_meta($item->get_id(), '_stock_dependency_restocked')) {
                            wc_add_order_item_meta(
                                $item->get_id(),
                                '_stock_dependency_restocked',
                                $refund_qty,
                                true
                            );
                        } else {
                            wc_update_order_item_meta(
                                $item->get_id(),
                                '_stock_dependency_restocked',
                                $refund_qty
                            );
                        }

                        $order->add_order_note(sprintf(
                            __('[Refunded] Restocked order stock for dependency SKU %s from %s to %s [+%s]', 'woocommerce'),
                            $dependency_product->get_sku(),
                            $old_qty,
                            $old_qty + (-1 * $restock_qty),
                            -1 * $restock_qty
                        ));

                        do_action(
                            'wcsd_refunded_order_item_dependency_stock_restocked',
                            absint($restock_qty),
                            absint($old_qty),
                            $dependency_product
                        );
                    }
                }

                $this->reset_product_stock_quantity(wc_get_product($item->get_product_id()), $order);
            }
        }

        /**
         * Restock cancelled order
         *
         * @param int $order_id
         * @return void
         */
        public function restock_cancelled_order($order_id): void {
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }

            foreach ($order->get_items() as $item) {
                $dependencies = $this->get_order_item_stock_dependencies($item);
                if (!$dependencies || !$dependencies->enabled) {
                    continue;
                }

                $previously_refunded = $this->get_order_item_refunded_qty($item) ?: 0;
                $restock_qty = $item->get_quantity() + $previously_refunded;

                $this->restock_order_item($order, $item, $dependencies, $restock_qty);
            }
        }

        /**
         * Restock order item
         *
         * @param \WC_Order $order
         * @param \WC_Order_Item $item
         * @param object $dependencies
         * @param int $restock_qty
         * @return void
         */
        public function restock_order_item($order, $item, $dependencies, $restock_qty): void {
            if (!$dependencies->enabled) {
                return;
            }

            foreach ($dependencies->stock_dependency as $dependency) {
                $product = $this->get_product_by_sku($dependency->sku);
                if (!$product) {
                    continue;
                }

                $qty = $dependency->qty * $restock_qty;
                $result = wc_update_product_stock($product, $qty, 'increase');

                if ($result !== false) {
                    $order->add_order_note(sprintf(
                        __('[Cancelled] Restocked order stock for dependency SKU %s [+%s]', 'woocommerce'),
                        $dependency->sku,
                        $qty
                    ));
                } else {
                    $order->add_order_note(sprintf(
                        __('[Cancelled] Unable to restock stock for dependency SKU %s [+%s]', 'woocommerce'),
                        $dependency->sku,
                        $qty
                    ));
                }

                do_action(
                    'wcsd_order_item_dependency_stock_restocked',
                    absint($restock_qty),
                    absint($dependency->qty),
                    $product
                );
            }
        }

        /**
         * Reset product stock quantity
         *
         * @param \WC_Product $product
         * @param \WC_Order $order
         * @return void
         */
        public function reset_product_stock_quantity($product, $order): void {
            $sku = $product->get_sku();
            $new_stock = wc_update_product_stock(
                $product,
                $this->product_get_stock_quantity(0, $product),
                'set'
            );

            if (is_wp_error($new_stock)) {
                $order->add_order_note(sprintf(
                    __('Unable to set stock for SKU %s to %d', 'woocommerce'),
                    $sku,
                    $new_stock
                ));
            } else {
                $order->add_order_note(sprintf(
                    __('Set order stock for SKU %s to %d', 'woocommerce'),
                    $sku,
                    $new_stock
                ));
            }
        }

        /**
         * Hide order item meta
         *
         * @param array $args
         * @return array
         */
        public function hidden_order_itemmeta(array $args): array {
            return array_merge($args, [
                '_stock_dependency',
                '_stock_dependency_reduced',
                '_stock_dependency_restocked'
            ]);
        }

        /**
         * Display item dependencies in admin
         *
         * @param int $item_id
         * @param \WC_Order_Item $item
         * @param \WC_Product $product
         * @return void
         */
        public function display_item_dependencies_in_admin($item_id, $item, $product): void {
            if (!$item->meta_exists('_stock_dependency')) {
                return;
            }

            $dependencies = $this->get_stock_dependency_meta($item);
            $settings = json_decode($dependencies);
            
            if (!$settings || !$settings->enabled) {
                return;
            }

            echo '<div class="meta" style="margin-left: 10px;">';
            echo '<strong>Stock Dependencies</strong>';
            
            foreach ($settings->stock_dependency as $dependency) {
                $dependency_product = wc_get_product($dependency->product_id);
                if (!$dependency_product) {
                    continue;
                }

                $edit_link = $dependency_product->get_type() === 'variation'
                    ? get_edit_post_link($dependency_product->get_parent_id())
                    : get_edit_post_link($dependency_product->get_id());

                printf(
                    '<div class="wc-order-item-sku"><strong>SKU</strong>: <a href="%s">%s</a>&nbsp;<strong>Qty</strong>: %d</div>',
                    esc_url($edit_link),
                    esc_html($dependency->sku),
                    intval($dependency->qty)
                );
            }
            
            echo '</div>';
        }

        /**
         * Add action links
         *
         * @param array $links
         * @return array
         */
        public function action_links(array $links): array {
            $links[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/kmac420/stock-dependencies-for-woocommerce#stock-dependencies-for-woocommerce-plugin',
                __('Documentation', 'woocommerce')
            );
            
            $links[] = sprintf(
                '<a href="%s">%s</a>',
                admin_url('tools.php?page=stock-dependencies-settings'),
                __('Tools', 'woocommerce')
            );
            
            return $links;
        }

        /**
         * Enqueue admin scripts
         *
         * @param string $hook
         * @return void
         */
        public function enqueu_scripts($hook): void {
            if ($hook !== 'post.php') {
                return;
            }

            global $post;
            if (get_post_type($post) !== 'product') {
                return;
            }

            wp_enqueue_script(
                'sdwc_admin_settings',
                plugins_url("/settings.js", __FILE__),
                [],
                '1.6.3',
                true
            );
            
            wp_enqueue_style(
                'sdwc_admin_styles',
                plugins_url("/admin.css", __FILE__),
                [],
                '1.6.3'
            );
        }

        /**
         * Get all stock dependency settings
         *
         * @return array
         */
        public function get_all_stock_dependency_settings(): array {
            global $wpdb;
            
            return $wpdb->get_results($wpdb->prepare(
                "SELECT post_id, meta_value
                FROM {$wpdb->postmeta}
                WHERE meta_key = %s",
                self::STOCK_DEPENDENCY_META_KEY
            ));
        }

        /**
         * Delete all stock dependency transients
         *
         * @return int Number of deleted transients
         */
        public function delete_all_stock_dependency_transients(): int {
            global $wpdb;
            
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options}
                WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . self::TRANSIENT_PREFIX) . '%'
            ));

            if ($count > 0) {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->options}
                    WHERE option_name LIKE %s",
                    $wpdb->esc_like('_transient_' . self::TRANSIENT_PREFIX) . '%'
                ));
            }

            return (int) $count;
        }

        /**
         * Render settings page
         *
         * @return void
         */
        public function settings_page_html(): void {
            if (!current_user_can('manage_options')) {
                return;
            }

            $clear_transients = filter_input(INPUT_GET, 'clear-transients', FILTER_VALIDATE_BOOLEAN);
            $check_dependencies = filter_input(INPUT_GET, 'check-dependencies', FILTER_VALIDATE_BOOLEAN);
            $sku = filter_input(INPUT_GET, 'sku', FILTER_DEFAULT) ?? '';

            require_once dirname(__FILE__) . '/views/settings-page.php';
        }

        /**
         * Add settings page to admin menu
         *
         * @return void
         */
        public function settings_page(): void {
            add_submenu_page(
                'tools.php',
                'Stock Dependencies',
                'Stock Dependencies',
                'manage_options',
                'stock-dependencies-settings',
                [$this, 'settings_page_html']
            );
        }
    }
}
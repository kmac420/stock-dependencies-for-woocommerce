<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin settings page template
 * 
 * @var bool $clear_transients Whether to clear transients
 * @var bool $check_dependencies Whether to check dependencies
 * @var string $sku Product SKU to check
 */
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2><?php esc_html_e('Remove Stock Dependency Plugin DB Transients', 'woocommerce'); ?></h2>
    <p>
        <?php esc_html_e('This plugin uses WordPress transients to store some stock dependency settings for each product, in order to improve performance. These will be automatically cleaned up by WordPress and recreated by the plugin as needed, but if your site is not working correctly you can remove the plugin transients. Doing this will not break anything but your site might perform slower until the transients are recreated when each product is viewed in your store.', 'woocommerce'); ?>
    </p>
    
    <a class="submit button button-primary" href="<?php echo esc_url(admin_url('tools.php?page=stock-dependencies-settings&clear-transients=true')); ?>">
        <?php esc_html_e('Clear Plugin Transients', 'woocommerce'); ?>
    </a>

    <?php if ($clear_transients): ?>
        <p>
            <?php esc_html_e('Clearing transients ... ', 'woocommerce'); ?>
            <?php 
            $num_transients = $this->delete_all_stock_dependency_transients();
            if ($num_transients === 0): ?>
                <?php esc_html_e('No transients to clear', 'woocommerce'); ?>
            <?php else: ?>
                <span style="color:green;"><?php esc_html_e('Done!', 'woocommerce'); ?></span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <h2><?php esc_html_e('Check Stock Dependencies', 'woocommerce'); ?></h2>
    <p>
        <?php esc_html_e('Check the stock dependencies for a product by inputting the product SKU and click the "Check" button. The plugin will use the configured stock dependencies and will determine the available inventory based on the dependencies the same way it is calculated in your store.', 'woocommerce'); ?>
    </p>

    <form method="get" action="<?php echo esc_url(admin_url('tools.php')); ?>">
        <input type="hidden" name="page" value="stock-dependencies-settings">
        <input type="hidden" name="check-dependencies" value="true">
        
        <label for="sku"><?php esc_html_e('Product SKU:', 'woocommerce'); ?></label><br>
        <input type="text" id="sku" name="sku" value="<?php echo esc_attr($sku); ?>" />
        
        <p>
            <input type="submit" class="submit button button-primary" value="<?php esc_attr_e('Check', 'woocommerce'); ?>">
        </p>
    </form>

    <?php if ($check_dependencies): ?>
        <?php $product = $this->get_product_by_sku($sku); ?>
        
        <?php if ($product): ?>
            <?php $settings = $this->get_stock_dependency_settings($product); ?>
            
            <?php if ($settings): ?>
                <strong><?php esc_html_e('Product', 'woocommerce'); ?></strong><br>
                <?php
                $edit_link = $product->is_type('variation') 
                    ? get_edit_post_link($product->get_parent_id())
                    : get_edit_post_link($product->get_id());
                ?>
                <?php esc_html_e('Product name:', 'woocommerce'); ?> 
                <a href="<?php echo esc_url($edit_link); ?>">
                    <?php echo esc_html($product->get_name()); ?>
                </a><br>
                
                <?php esc_html_e('Product SKU:', 'woocommerce'); ?> 
                <?php echo esc_html($product->get_sku()); ?><br>
                
                <?php esc_html_e('Dependencies enabled:', 'woocommerce'); ?> 
                <?php echo $settings->enabled ? 'true' : 'false'; ?><br>
                
                <?php esc_html_e('Calculated inventory:', 'woocommerce'); ?> 
                <?php echo esc_html($this->product_get_stock_quantity(1, $product)); ?><br>

                <?php foreach ($settings->stock_dependency as $key => $dependency): ?>
                    <div style="margin:20px;">
                        <strong><?php printf(esc_html__('Dependency #%d', 'woocommerce'), $key + 1); ?></strong><br>
                        <?php $dependency_product = $this->get_product_by_sku($dependency->sku); ?>
                        
                        <?php if ($dependency_product): ?>
                            <?php
                            $dependency_edit_link = $dependency_product->is_type('variation')
                                ? get_edit_post_link($dependency_product->get_parent_id())
                                : get_edit_post_link($dependency_product->get_id());
                            ?>
                            <?php esc_html_e('Dependency name:', 'woocommerce'); ?> 
                            <a href="<?php echo esc_url($dependency_edit_link); ?>">
                                <?php echo esc_html($dependency_product->get_name()); ?>
                            </a><br>
                            
                            <?php esc_html_e('Dependency SKU:', 'woocommerce'); ?> 
                            <?php echo esc_html($dependency_product->get_sku()); ?><br>
                            
                            <?php esc_html_e('Dependency quantity:', 'woocommerce'); ?> 
                            <?php echo esc_html($dependency->qty); ?><br>
                            
                            <?php esc_html_e('Dependency inventory:', 'woocommerce'); ?> 
                            <?php echo esc_html($dependency_product->get_stock_quantity()); ?><br>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>
                    <?php esc_html_e('There are no stock dependencies for product', 'woocommerce'); ?>
                    <a href="<?php echo esc_url($edit_link); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p>
                <?php printf(
                    esc_html__('There is no product with SKU: %s', 'woocommerce'),
                    esc_html($sku)
                ); ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>
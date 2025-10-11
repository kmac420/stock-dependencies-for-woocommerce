# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Stock Dependencies for WooCommerce is a WordPress plugin that allows WooCommerce products and variations to depend on the inventory of other products or variations. This is useful for selling products in multiple quantities (e.g., 6-pack, 12-pack) or bundled products without managing separate inventory for each variant.

## Development Commands

### Testing

```bash
# Install dependencies (run from plugin root directory)
composer install

# Run all tests
composer test
# or
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/AdminTest.php

# Run tests with coverage (if configured)
vendor/bin/phpunit --coverage-html coverage
```

### PHP Version

Requires PHP >= 7.2

## Architecture

### Core Components

**Main Plugin File**: `wc-stock-dependencies.php`
- Entry point that registers all WordPress/WooCommerce hooks
- Uses namespaces: `StockDependenciesForWooCommerce` (main) and `StockDependenciesForWooCommerceAdmin` (admin class)
- Declares compatibility with WooCommerce High-Performance Order Storage (HPOS)

**Admin Class**: `admin.php`
- Contains all business logic in a single `Admin` class
- Handles product configuration, stock calculations, order processing, and refunds
- Not an actual singleton despite appearing to instantiate multiple times in hooks (WordPress behavior)

### Key Architectural Patterns

**Transient Caching Strategy**
- Stock dependency settings stored in both product meta (`_stock_dependency`) and transients (`sdwc-product-settings-{product_id}`)
- Product meta stores SKU but NOT product ID (IDs can change during imports/migrations)
- Transients store fully resolved settings including product IDs for performance (7-day TTL)
- `get_stock_dependency_settings()` checks transient first, falls back to meta, then rebuilds transient

**Data Flow for Stock Dependencies**
1. Settings saved via admin UI → `admin_process_product_object()` or `save_product_variation()`
2. Data validated via `validate_product_data()` (JSON validation)
3. Product IDs resolved via `update_product_data()` (SKU → product ID lookup)
4. Settings cached via `save_dependency_transient()`

**Stock Calculation Hooks**
- Filters: `woocommerce_product_get_stock_quantity`, `woocommerce_product_variation_get_stock_quantity`
- Logic: For each dependency, calculate available units as `floor(dependency_stock / dependency_qty)`, return minimum
- Function: `product_get_stock_quantity()` at admin.php:343

**In-Stock Status Hooks**
- Filters: `woocommerce_product_is_in_stock`, `woocommerce_variation_is_in_stock`
- Handles variable products by checking all variations
- Updates `stock_status` in database to prevent sync issues
- Function: `product_is_in_stock()` at admin.php:382

### Order Processing Lifecycle

**Order Creation/Checkout**
1. Hook: `woocommerce_before_save_order_items` → `before_save_order_items()`
2. Calls `reduce_order_stock()` at admin.php:520
3. For each item with dependencies:
   - Reduces dependency product stock by `order_qty * dependency_qty`
   - Adds order notes documenting stock changes
   - Sets `_stock_dependency_reduced` meta to prevent duplicate reductions
   - Saves dependency settings snapshot to `_stock_dependency` order item meta
   - Resets parent product stock via `reset_product_stock_quantity()`

**Refunds**
1. Hook: `woocommerce_restock_refunded_item` → `restock_refunded_item()`
2. Retrieves original dependency settings from order item meta
3. Calculates delta between new refund qty and previously refunded qty
4. Restores dependency stock incrementally
5. Updates `_stock_dependency_restocked` meta

**Cancellations**
1. Hook: `woocommerce_order_status_cancelled` → `restock_cancelled_order()`
2. Accounts for partial refunds already processed
3. Restocks remaining quantity: `item_qty + previously_refunded`
4. Function: `restock_cancelled_order()` at admin.php:707

### Frontend Assets

**JavaScript**: `settings.js`
- Handles dependency UI in product edit screen
- Dynamic add/remove of dependency rows
- Prevents self-referencing SKUs

**CSS**: `admin.css`
- Styles for stock dependency fields in WP admin

### Testing

**Test Framework**
- PHPUnit 9.x with WP_Mock
- Bootstrap file at `tests/bootstrap.php` provides WooCommerce class stubs
- Test base class: `WP_Mock_Test_Case` handles WP_Mock setup/teardown

**Test Files**
- `AdminTest.php`: Unit tests for core Admin class methods
- `AdminComplexTest.php`: Integration/complex scenario tests

**Testing Notes**
- WooCommerce functions mocked via `\WP_Mock::userFunction()`
- Product/order objects use lightweight stubs from bootstrap
- Focus on testing business logic, not WordPress/WooCommerce internals

## Important Implementation Details

### SKU vs Product ID
- Always store SKUs in meta, resolve to product IDs at runtime
- `update_product_data()` validates SKUs and adds/updates product IDs in transients
- Invalid SKUs are removed from dependency arrays

### Stock Status Synchronization
- WordPress/WooCommerce can have stale `stock_status` in database
- Plugin explicitly calls `set_stock_status()` to keep in sync
- Variable products check all variations to determine parent status

### Order Item Meta (Hidden from Customer)
- `_stock_dependency`: Snapshot of settings when order placed
- `_stock_dependency_reduced`: Flag to prevent duplicate stock reduction
- `_stock_dependency_restocked`: Tracks refunded quantity
- Hidden via `woocommerce_hidden_order_itemmeta` filter at admin.php:825

### Admin Tools Page
- Located at WP Admin → Tools → Stock Dependencies
- Clear transients utility for troubleshooting
- SKU lookup tool shows calculated inventory and dependency chain
- Function: `settings_page_html()` at admin.php:968

## Common Gotchas

1. **WooCommerce "Hold stock" setting must be disabled** (set to 0 minutes) or plugin won't work correctly
2. **Backorders require cascading enablement**: Both dependency and dependent products must allow backorders
3. **Variable products with stock at variation level**: Parent product stock status determined by checking all child variations
4. **Transients can get stale**: Use admin tools page to clear if behavior seems incorrect
5. **Order of hook execution matters**: `reduce_order_stock` happens AFTER WooCommerce reduces product stock, so dependent product stock is reset to calculated value

## WooCommerce Compatibility

- Requires WooCommerce 4.0+
- Tested up to WooCommerce 8.7
- HPOS compatible (declared in main plugin file via `FeaturesUtil::declare_compatibility`)

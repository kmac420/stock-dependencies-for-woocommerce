# Stock Dependencies for WooCommerce - Tests

This directory contains unit tests for the Stock Dependencies for WooCommerce plugin using PHPUnit and WP_Mock.

## Overview

The test suite uses:
- [PHPUnit](https://phpunit.de/) for the testing framework
- [WP_Mock](https://github.com/10up/wp_mock) for mocking WordPress functions and classes

## Installation

1. Install dependencies using Composer:
```bash
cd wc-stock-dependencies
composer install
```

2. This will install PHPUnit, WP_Mock, and other dependencies required for testing.

## Running Tests

To run the entire test suite:
```bash
cd wc-stock-dependencies
./vendor/bin/phpunit
```

To run a specific test file:
```bash
./vendor/bin/phpunit tests/AdminTest.php
```

## Test Structure

The tests are organized into the following files:

- **AdminTest.php**: Tests for the simpler Admin class methods
- **AdminComplexTest.php**: Tests for the more complex Admin class methods

### Mocking Approach

WP_Mock allows us to mock WordPress and WooCommerce functions and classes. This is done using:

1. `\WP_Mock::userFunction()` for mocking WordPress functions
2. `createMock()` and `getMockBuilder()` for mocking classes

Example of mocking a WordPress function:
```php
\WP_Mock::userFunction('wc_get_product_id_by_sku', [
    'args' => [$sku],
    'times' => 1,
    'return' => $product_id
]);
```

Example of mocking a class method:
```php
$product = $this->createMock(\WC_Product::class);
$product->method('get_meta')
    ->with('_stock_dependency')
    ->willReturn($product_meta);
```

## Test Helpers

The `bootstrap.php` file sets up the test environment by:
1. Loading the Composer autoloader
2. Initializing WP_Mock
3. Defining mock classes for WooCommerce products and orders
4. Defining a custom test case for WP_Mock

## Adding New Tests

When adding new tests:

1. Extend the `\WP_Mock_Test_Case` class
2. Create a setUp method that initializes the Admin class
3. Create test methods starting with `test_`
4. Mock WordPress/WooCommerce functions as needed
5. Call the method under test
6. Make assertions about the expected behavior

Example:
```php
public function test_method_name() {
    // Setup mocks
    \WP_Mock::userFunction('wp_function', [
        'return' => 'expected value'
    ]);
    
    // Call the method under test
    $result = $this->admin->method_name();
    
    // Assert the expected behavior
    $this->assertEquals('expected value', $result);
}
```

## Coverage

The tests aim to cover all functions in the Admin class, which is the main class of the plugin.
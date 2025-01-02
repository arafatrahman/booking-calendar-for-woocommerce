<?php
/**
 * Plugin Name: WooCommerce Booking Subscription MVC
 * Description: Adds a booking date selection for products with customizable subscription durations in an MVC pattern.
 * Version:     1.0
 * Author:      Your Name
 * Text Domain: wc-booking-subscription-mvc
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize the plugin.
 */
add_action('plugins_loaded', 'wcbs_initialize_plugin');
function wcbs_initialize_plugin() {
    require_once __DIR__ . '/includes/model.php';
    require_once __DIR__ . '/includes/controller.php';
    require_once __DIR__ . '/includes/view.php';

 //   WCBS_Controller::init();
}
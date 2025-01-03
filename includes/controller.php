<?php
class WCBS_Controller {

    public static function init() {
        $instance = new self();
    }

    public function __construct() {
        // Initialize WooCommerce hooks
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_booking_date_to_cart'], 10, 2);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_booking_date'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_booking_and_expiry_cart'], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'change_add_to_cart_button_text'], 10, 1);
        add_action('woocommerce_before_add_to_cart_form', [$this, 'replace_add_to_cart_button_logic']);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'replace_shop_add_to_cart_button'], 10, 2);
        add_action('admin_menu', [$this, 'add_booking_calendar_menu']);
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_product_booking_fields']);
        add_action('woocommerce_process_product_meta', [$this,'wcbs_save_booking_options'], 10, 1);
        add_action('woocommerce_before_add_to_cart_button', [$this,'wcbs_display_booking_date_field']);

        add_action('woocommerce_order_item_meta_end',  [$this,'wcbs_display_booking_date_in_email_meta_end'], 10, 4);
        add_action('woocommerce_email_after_order_table', [$this,'wcbs_custom_thank_you_message'], 10, 4);
        add_action('woocommerce_checkout_create_order_line_item', [$this,'wwcbs_save_booking_date_to_order'], 10, 4);

    }

    public function wwcbs_save_booking_date_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['wc_booking_date'])) {
            $booking_date = $values['wc_booking_date'];
            $expiry_duration = $values['wc_expiry_duration'];
            $promo_code_usage_limit = $values['wc_promo_code_usage_limit'];
    
            // Calculate expiration date
            if ($expiry_duration === '4_weeks') {
                $expiration_date = date('Y-m-d', strtotime($booking_date . ' +4 weeks'));
            } elseif ($expiry_duration === '6_months') {
                $expiration_date = date('Y-m-d', strtotime($booking_date . ' +6 months'));
            } elseif ($expiry_duration === '1_year') {
                $expiration_date = date('Y-m-d', strtotime($booking_date . ' +1 year'));
            } else {
                $expiration_date = __('Unlimited', 'wc-booking-subscription');
            }
    
            $item->add_meta_data(__('WP_Booking_Start_Date', 'wc-booking-subscription'), $booking_date);
            $item->add_meta_data(__('WP_Booking_End_Date', 'wc-booking-subscription'), $expiration_date);
            $item->add_meta_data(__('WP_Promo_Code_Usage_Limit', 'wc-booking-subscription'), $promo_code_usage_limit);
    
        }
    }

    public function wcbs_custom_thank_you_message($order, $sent_to_admin, $plain_text, $email) {
        if (!$sent_to_admin) {
            // Custom message content
            $custom_message = __('Thank you for your purchase! We appreciate your business. We hope you enjoy your product! If you have any questions, don\'t hesitate to reach out to us.', 'wc-booking-subscription');
    
            // Add a line break before the message for better formatting
            echo '<p>' . esc_html($custom_message) . '</p>';
            // Optionally, add the review request link as well
            echo '<p><a href="https://g.co/kgs/KkNYQT1" target="_blank"><strong>' . __("We\'d Love Your Feedback! Please Leave Us a Review Here", 'wc-booking-subscription') . '</strong></a></p>';
        }
    }

    public function wcbs_display_booking_date_in_email_meta_end($item_id, $item, $order, $plain_text) {
        // Get the booking date from the order item meta
        
        $generated_coupon_code = 'BOOKING-' . strtoupper(uniqid()) . '-' . substr($item->get_name(), 0, 3);
        $product_id = $item->get_product_id();
        // Now you can use the $product_id to retrieve product meta or other details
        $wcbs_booking_options = get_post_meta($product_id, '_wcbs_booking_options', true);
    
        $is_booking_enabled = isset($wcbs_booking_options['enable_booking_date']) ? $wcbs_booking_options['enable_booking_date'] : 'no';
    
        if ($is_booking_enabled != "yes") {
            return;
        }
    
        $limit = $wcbs_booking_options['promo_code_usage_limit'];
    
        $booking_date = wc_get_order_item_meta($item_id, 'WP_Booking_Start_Date', true);
        $expiry_date = wc_get_order_item_meta($item_id, __('WP_Booking_End_Date', 'wc-booking-subscription'), true );
        $promo_code_usage_limit = wc_get_order_item_meta($item_id, __('WP_Promo_Code_Usage_Limit', 'wc-booking-subscription'), true );
    
    
    
        // Retrieve meta data for the item
    
                
        // Define coupon details
        $discount_type = 'percent'; // 50% discount
        $coupon_amount = 50; 
        $usage_limit = $promo_code_usage_limit; // Single-use by default
        $expiry_duration = $expiry_date; // Fetch expiry duration set for the product

        $applied_coupons = $order->get_coupon_codes();
    
        $coupon = new WC_Coupon();
        $coupon->set_code($generated_coupon_code);
        $coupon->set_discount_type($discount_type);
        $coupon->set_amount($coupon_amount);
        $coupon->set_individual_use(true); // Cannot combine with other coupons
        $coupon->set_usage_limit($usage_limit); 
        if ($expiry_date) {
          $coupon->set_date_expires(strtotime($expiry_date));
        }
        $coupon->save(); 

        }
        
        // If a booking date exists, display it in the email
        if ($booking_date) {
            // Display booking date at the end of the item meta section in the email
            echo '<p><strong>' . __('Booking Start Date:', 'wc-booking-subscription') . '</strong> ' . esc_html($booking_date) . '</p>';
            echo '<p><strong>' . __('Promo Code:', 'wc-booking-subscription') . '</strong> ' . $generated_coupon_code . '</p>';
            echo '<p><strong>Promo Code Expiry Date:</strong> ' . esc_html($expiry_date) . '</p>';
            echo '<p><strong>Usage Limit:</strong> ' . esc_html($usage_limit) . '</p>';
        
        }
    }



    /**
     * Add booking date field on the product page if enabled.
     */
    public static function wcbs_display_booking_date_field() {
        global $product;

        $wcbs_booking_options = get_post_meta($product->get_id(), '_wcbs_booking_options', true);
        $is_booking_enabled = isset($wcbs_booking_options['enable_booking_date']) ? $wcbs_booking_options['enable_booking_date'] : 'no';
    
        if ('yes' === $is_booking_enabled) {
            echo '<div class="wc-booking-date-field">';
            echo '<label for="wc_booking_date">' . __('Select Booking Start Date:', 'wc-booking-subscription') . '</label>';
            echo '<input type="date" id="wc_booking_date" name="wc_booking_date" required />';
            echo '</div>';
        }
    }

    public static function wcbs_save_booking_options($post_id){

        // Define the values you want to save
        $booking_options = array(
            'enable_booking_date' => isset($_POST['_enable_booking_date']) ? 'yes' : 'no',
            'expiry_duration' => isset($_POST['_expiry_duration']) ? sanitize_text_field($_POST['_expiry_duration']) : '6_months',
            'enable_promo_code' => isset($_POST['_enable_promo_code']) ? sanitize_text_field($_POST['_enable_promo_code']) : 'no', // Default value changed to 'no'
            'promo_code_usage_limit' => isset($_POST['_promo_code_usage_limit']) ? sanitize_text_field($_POST['_promo_code_usage_limit']) : 'no_limit' // Default value changed to 'no_limit'
        );
        WCBS_Model::save_wcbs_product_meta($post_id, '_wcbs_booking_options', $booking_options);
    }

    /**
     * Add booking fields to product settings.
     */
    public static function add_product_booking_fields() {
        WCBS_View::render_product_booking_fields();
    }

    /**
     * Add Booking Calendar menu.
     */
    public static function add_booking_calendar_menu() {
        add_menu_page(
            __('Booking Calendar', 'wc-booking-subscription-mvc'),
            __('Booking Calendar', 'wc-booking-subscription-mvc'),
            'manage_woocommerce',
            'wc-booking-calendar',
            [__CLASS__, 'render_booking_calendar_page'],
            'dashicons-calendar-alt',
            25
        );
    }


    // Add booking date to cart item data
    public function add_booking_date_to_cart($cart_item_data, $product_id) {
        // Logic to add booking date
        
        if (isset($_POST['wc_booking_date'])) {
            $cart_item_data['wc_booking_date'] = sanitize_text_field($_POST['wc_booking_date']);
        }

        $wcbs_booking_options = get_post_meta($product_id, '_wcbs_booking_options', true);
        if (isset($wcbs_booking_options['promo_code_usage_limit'])) {
            $cart_item_data['wc_promo_code_usage_limit'] = $wcbs_booking_options['promo_code_usage_limit'];
        }else{
            $cart_item_data['wc_promo_code_usage_limit'] = 1;
        }
        if (isset($wcbs_booking_options['expiry_duration'])) {
            $cart_item_data['wc_expiry_duration'] = $wcbs_booking_options['expiry_duration'];
        }else{
            $cart_item_data['wc_expiry_duration'] = '4_weeks';
        }
    
        return $cart_item_data;
    }

    // Validate booking date before adding to cart
    public function validate_booking_date($passed, $product_id, $quantity) {

        $wcbs_booking_options = get_post_meta($product_id, '_wcbs_booking_options', true);
        $is_booking_enabled = isset($wcbs_booking_options['enable_booking_date']) ? $wcbs_booking_options['enable_booking_date'] : 'no';
        if (empty($_POST['wc_booking_date']) && 'yes' === $is_booking_enabled) {
            wc_add_notice(__('Please select a booking date.', 'wcbs'), 'error');
            $passed = false;
        }
        return $passed;
    }

    // Display booking and expiry data in the cart
    public function display_booking_and_expiry_cart($item_data, $cart_item) {

        if (isset($cart_item['wc_booking_date'])) {
            $booking_date = $cart_item['wc_booking_date'];
            $expiry_duration = $cart_item['wc_expiry_duration'];
            $promo_code_usage_limit = $cart_item['wc_promo_code_usage_limit'];
    
    
            // Calculate expiration date based on selected duration
            if ($expiry_duration === '4_weeks') {
                $expiration_date = date('Y-m-d', strtotime($booking_date . ' +4 weeks'));
            } elseif ($expiry_duration === '6_months') {
                $expiration_date = date('Y-m-d', strtotime($booking_date . ' +6 months'));
            } elseif ($expiry_duration === '1_year') {
                $expiration_date = date('Y-m-d', strtotime($booking_date . ' +1 year'));
            } else {
                $expiration_date = __('4 Weeks', 'wc-booking-subscription');
            }
    
            $item_data[] = [
                'name'  => __('Booking Start Date', 'wc-booking-subscription'),
                'value' => $booking_date,
            ];
            $item_data[] = [
                'name'  => __('Booking End Date', 'wc-booking-subscription'),
                'value' => $expiration_date,
            ];

        }
        return $item_data;
    }

    // Save booking date to order meta



    // Change the "Add to Cart" button text for single products
    public function change_add_to_cart_button_text($text) {
        global $product;
        $wcbs_booking_options = get_post_meta($product->get_id(), '_wcbs_booking_options', true);
        $is_booking_enabled = isset($wcbs_booking_options['enable_booking_date']) ? $wcbs_booking_options['enable_booking_date'] : 'no';
        if ('yes' === $is_booking_enabled) {
        return __('Book Now', 'wc-booking-subscription');
        }
        return $text;
    }

    
    
    

    
    

    /**
     * Replace the "Add to Cart" button functionality with booking logic.
     */
    
     public function replace_add_to_cart_button_logic() {
        global $product;

        $wcbs_booking_options = get_post_meta($product->get_id(), '_wcbs_booking_options', true);
        $is_booking_enabled = isset($wcbs_booking_options['enable_booking_date']) ? $wcbs_booking_options['enable_booking_date'] : 'no';
        if ('yes' === $is_booking_enabled) {
            // Remove the default WooCommerce add to cart button
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            // Add custom booking button
            add_action('woocommerce_single_product_summary', 'wcbs_custom_booking_button', 30);
        }
    }

    

    // Replace the Add to Cart button in the shop loop
    public function replace_shop_add_to_cart_button($button, $product) {

        $wcbs_booking_options = get_post_meta($product->get_id(), '_wcbs_booking_options', true);
        $is_booking_enabled = isset($wcbs_booking_options['enable_booking_date']) ? $wcbs_booking_options['enable_booking_date'] : 'no';
        
        if ('yes' === $is_booking_enabled) {
            return '<a href="' . esc_url($product->get_permalink()) . '" class="button alt wcbs-booking-loop-btn">' . esc_html__('Book Now', 'wcbs') . '</a>';
        }
        return $button;
    }

    
}

// Initialize the controller
new WCBS_Controller();

?>

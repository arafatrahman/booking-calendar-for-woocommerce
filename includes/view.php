<?php 

class WCBS_View {
    /**
     * Render booking fields in product settings
     */
    private static $fields_rendered = false;

    public static function render_product_booking_fields() {
        global $post;

        $wcbs_booking_options = get_post_meta($post->ID, '_wcbs_booking_options', true);
        // Enable booking date checkbox
        woocommerce_wp_checkbox([
            'id'          => '_enable_booking_date',
            'label'       => __('Enable Booking Date', 'wc-booking-subscription-mvc'),
            'description' => __('Allow customers to select a booking start date.', 'wc-booking-subscription-mvc'),
            'desc_tip'    => true,
            'value'       => isset($wcbs_booking_options['enable_booking_date']) ? $wcbs_booking_options['enable_booking_date'] : 'no',

        ]);

        // Expiry duration dropdown
        woocommerce_wp_select([
            'id'      => '_expiry_duration',
            'label'   => __('Expiry Duration', 'wc-booking-subscription-mvc'),
            'options' => [
                '4_weeks'   => __('4 Weeks', 'wc-booking-subscription-mvc'),
                '6_months'  => __('6 Months', 'wc-booking-subscription-mvc'),
                '1_year'    => __('1 Year', 'wc-booking-subscription-mvc'),
                'unlimited' => __('Unlimited', 'wc-booking-subscription-mvc'),
            ],
            'value'   => isset($wcbs_booking_options['expiry_duration']) ? $wcbs_booking_options['expiry_duration'] : '6_months',

        ]);

        // Promo code fields
        woocommerce_wp_checkbox([
            'id'          => '_enable_promo_code',
            'label'       => __('Enable Promo Code', 'wc-booking-subscription-mvc'),
            'description' => __('Enable promo code generation for this product.', 'wc-booking-subscription-mvc'),
            'value'       => isset($wcbs_booking_options['enable_promo_code']) ? $wcbs_booking_options['enable_promo_code'] : 'no',

        ]);

        woocommerce_wp_text_input([
            'id'          => '_promo_code_usage_limit',
            'label'       => __('Promo Code Usage Limit', 'wc-booking-subscription-mvc'),
            'description' => __('Enter how many times the promo code can be used.', 'wc-booking-subscription-mvc'),
            'type'        => 'number',
            'value'       => isset($wcbs_booking_options['promo_code_usage_limit']) ? $wcbs_booking_options['promo_code_usage_limit'] : 'no_limit',

        ]);
    }

    /**
     * Render booking calendar page.
     */
    public static function render_booking_calendar() {
        ?>
        <div class="wrap">
            <h1><?php _e('Booking Calendar', 'wc-booking-subscription-mvc'); ?></h1>
            <div id="booking-calendar"></div>
        </div>

        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('booking-calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?php echo json_encode(WCBS_Model::get_bookings_for_calendar()); ?>
            });
            calendar.render();
        });
        </script>

        <style>
            #booking-calendar {
                max-width: 900px;
                margin: 20px auto;
            }
        </style>
        <?php
    }
}


?>
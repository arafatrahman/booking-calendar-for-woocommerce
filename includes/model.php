<?php 

class WCBS_Model {
    /**
     * Save product metadata.
     */
    public static function save_wcbs_product_meta($post_id, $meta_key, $meta_value) {
        update_post_meta($post_id, $meta_key, $meta_value);
    }

    /**
     * Fetch product metadata.
     */
    public static function get_product_meta($post_id, $meta_key, $default = '') {
        return get_post_meta($post_id, $meta_key, true) ?: $default;
    }

    /**
     * Fetch all bookings for the calendar.
     */
    public static function get_bookings_for_calendar() {
        $bookings = [];
        $orders = wc_get_orders(['limit' => -1]);

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item_id => $item) {
                $booking_start_date = wc_get_order_item_meta($item_id, 'WP_Booking_Start_Date');
                $booking_end_date = wc_get_order_item_meta($item_id, 'WP_Booking_End_Date');

                if ($booking_start_date) {
                    $client_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    $client_phone = $order->get_billing_phone();

                    $bookings[] = [
                        'title' => sprintf(__('Order #%s - %s', 'wc-booking-subscription-mvc'), $order->get_id(), $item->get_name()),
                        'start' => $booking_start_date,
                        'end'   => $booking_end_date,
                        'description' => sprintf(
                            __('Name: %s, Phone: %s', 'wc-booking-subscription-mvc'),
                            $client_name,
                            $client_phone
                        ),
                    ];
                }
            }
        }

        return $bookings;
    }


}

?>
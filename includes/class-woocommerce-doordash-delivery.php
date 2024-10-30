<?php

/**
 * DoorDash Delivery Object
 *
 * @link       https://www.inverseparadox.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/includes
 */

/**
 * DoorDash Delivery Object
 *
 * Represents a DoorDash delivery, and contains all the datapoints
 * needed to create a delivery in the Drive API
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/includes
 * @author     Inverse Paradox <erik@inverseparadox.net>
 */
class Woocommerce_Doordash_Delivery {

	/**
	 * Data store for the delivery
	 * Overridden by API requests
	 *
	 * @var array
	 */
	protected $data = array(
		// 'external_delivery_id' => '',
		// 'locale' => '',
		// 'pickup_address' => '',
		// 'pickup_business_name' => '',
		// 'pickup_phone_number' => '',
		// 'pickup_instructions' => '',
		// 'pickup_reference_tag' => '',
		// 'dropoff_address' => '',
		// 'dropoff_business_name' => '',
		// 'dropoff_phone_number' => '',
		// 'dropoff_instructions' => '',
		// 'dropoff_contact_given_name' => '',
		// 'dropoff_contact_family_name' => '',
		// 'dropoff_contact_send_notifications' => true,
		// 'order_value' => 0,
		// 'currency' => '',
		// 'contactless_dropoff' => false,
		// 'tip' => 0,
		// 'pickup_time' => '',
		// 'dropoff_time' => '',
		// 'pickup_window' =>
		// array (
		// 	'start_time' => '',
		// 	'end_time' => '',
		// ),
		// 'dropoff_window' =>
		// array (
		// 	'start_time' => '',
		// 	'end_time' => '',
		// ),
	);

	/**
	 * Create the delivery object given the delivery data
	 *
	 * @param int|WC_Order|array $data Order ID, Order Object, or array of delivery data
	 */
	public function __construct( $data = null ) {
		if ( is_null( $data ) ) $this->create_from_session();
		if ( is_int( $data ) ) {
			$data = wc_get_order( $data ); // Get the order object
		}
		if ( is_a( $data, 'WC_Order' ) ) $this->create_from_order( $data );
		else if ( is_array( $data ) ) $this->create_from_array( $data );
	}

	/**
	 * Populate the delivery data from an array
	 *
	 * @param array $data Array of delivery data
	 * @return void
	 */
	public function create_from_array( $data ) {
		$this->data = wp_parse_args( $data, $this->data );
	}

	/**
	 * Populate the delivery data from an order object
	 *
	 * @param [type] $order
	 * @return void
	 */
	public function create_from_order( $order ) {
		// get the metadata from the order here
	}

	/**
	 * Create the delivery object based on data saved in the user's session
	 *
	 * @return void
	 */
	public function create_from_session() {
		// Get the location ID from the session and set up the location object
		$location_id = WC()->session->get( 'doordash_pickup_location' );
		$location = new Woocommerce_Doordash_Pickup_Location( $location_id );

		// Get the tip
		$tip = intval( WC()->session->get( 'doordash_tip_amount' ) * 100 );

		// Get the external delivery ID if already set
		$external_delivery_id = WC()->session->get( 'doordash_external_delivery_id' );
		if ( empty( $external_delivery_id ) ) $external_delivery_id = time() . WC()->cart->get_cart_hash();

		$data = array(
			'external_delivery_id' => $external_delivery_id, // time() . WC()->cart->get_cart_hash(), // . time(),
			'locale' => $this->get_locale(), //'en-US',
			'pickup_address' => $location->get_formatted_address(),
			'pickup_business_name' => $location->get_name(),
			'pickup_phone_number' => $location->get_phone_number(),
			'pickup_instructions' => $location->get_pickup_instructions() ? $location->get_pickup_instructions() : get_option( 'woocommerce_doordash_default_pickup_instructions' ),
			'pickup_reference_tag' => '',
			'dropoff_address' => $this->get_formatted_shipping_address_from_session(),
			'dropoff_business_name' => WC()->customer->get_shipping_company(),
			'dropoff_phone_number' => $this->get_formatted_shipping_phone_from_session(),
			'dropoff_instructions' => WC()->session->get( 'doordash_dropoff_instructions' ),
			'dropoff_contact_given_name' => WC()->customer->get_billing_first_name(),
			'dropoff_contact_family_name' => WC()->customer->get_billing_last_name(),
			'dropoff_contact_send_notifications' => apply_filters( 'wcdd_contact_send_notifications', true ),
			'order_value' => intval( WC()->cart->get_cart_contents_total() * 100 ),
			'currency' => get_woocommerce_currency(),
			'contactless_dropoff' => apply_filters( 'wcdd_contactless_dropoff', false ),
			'tip' => $tip,
			'pickup_time' => gmdate( "Y-m-d\TH:i:s\Z", time() + $this->get_lead_time() ), //'2022-08-08T17:20:28Z',// date( 'Y-m-d\TH:i:sP', time() + HOUR_IN_SECONDS ),
			// 'dropoff_time' => '',
			// 'pickup_window' => array (
			// 	'start_time' => '',
			// 	'end_time' => '',
			// ),
			// 'dropoff_window' => array (
			// 	'start_time' => '',
			// 	'end_time' => '',
			// ),
		);
		$this->create_from_array( $data );
	}

	/**
	 * Returns the shipping/delivery address as a comma separated string
	 *
	 * @return string Address string with commas separating the components
	 */
	public function get_formatted_shipping_address_from_session() {
		$address = '';

		if ( WC()->customer->get_shipping_address_1() ) $address .= WC()->customer->get_shipping_address_1() . ', ';

		if ( WC()->customer->get_shipping_address_2() ) $address .= WC()->customer->get_shipping_address_2() . ', ';

		$address .= WC()->customer->get_shipping_state() . ' ';

		$address .= WC()->customer->get_shipping_postcode();
		return $address;
	}

	/**
	 * Retrieves a phone number including the country code and +
	 *
	 * @return string Formatted phone number
	 */
	public function get_formatted_shipping_phone_from_session() {
		// Parse data from ajax request
		$post_data = array();
		parse_str( $_REQUEST['post_data'], $post_data );

		// Set the photo to the shipping phone or the billing phone 
		if ( array_key_exists( 'shipping_phone', $post_data ) && ! empty( $post_data['shipping_phone'] ) ) {
			$phone = wc_sanitize_phone_number( $post_data['shipping_phone'] );
		} else if ( array_key_exists( 'billing_phone', $post_data ) && ! empty( $post_data['billing_phone'] ) ) {
			$phone = wc_sanitize_phone_number( $post_data['billing_phone'] );
		} else {
			$phone = '18004444444'; // Drive API requires phone, use a dummy if it's not set yet
		}

		$phone = str_replace( [ '-', '(', ')', ' ', '+' ], '', $phone );
		if ( strlen( $phone ) == 10 ) $phone = '1' . $phone;
		return $phone; // return '+' . $phone;
	}

	/**
	 * Saves the delivery data to an order
	 *
	 * @return void
	 */
	public function save_to_order() {
		// save the metadata to the order here
	}

	/**
	 * JSON encode the data from this delivery
	 *
	 * @return string JSON encoded delivery data
	 */
	public function json() {
		return json_encode( $this->data );
	}

	/**
	 * Get the external delivery ID DoorDash uses to identify this delivery
	 *
	 * @return string External delivery ID
	 */
	public function get_id() {
		return $this->data['external_delivery_id'];
	}

	/**
	 * Set the external delivery ID to an arbitrary value
	 *
	 * @param string $id External delivery ID
	 * @return string External delivery ID
	 */
	public function set_id( $id ) {
		$this->data['external_delivery_id'] = $id;
		return $this->data['external_delivery_id'];
	}

	/**
	 * Get the rate quoted by DoorDash for the delivery
	 *
	 * @return int Quoted rate in cents
	 */
	public function get_quoted_rate() {
		return ( array_key_exists( 'fee', $this->data ) && ! empty( $this->data['fee'] ) ) ? $this->data['fee'] / 100 : 0;
	}

	/**
	 * Retrieve the fee associated with this delivery
	 * Gets the value that should be charged to the user based on admin settings
	 *
	 * @return int Fee in cents
	 */
	public function get_fee() {
		$quoted = $this->get_quoted_rate();

		$fees_mode = get_option( 'woocommerce_doordash_fees_mode' );
		$delivery_fee = get_option( 'woocommerce_doordash_delivery_fee' );

		if ( $fees_mode == 'no_rate' ) {
			return 0;
		} else if ( $fees_mode == 'quoted_rate' ) {
			return $quoted + (float) $delivery_fee;
		} else if ( $fees_mode == 'fixed_rate' ) {
			return $delivery_fee;
		}

		// If the option isn't set, return 0
		return 0;
	}

	/**
	 * Check if this is a valid delivery object for quoting
	 *
	 * @return boolean True if valid
	 */
	public function is_valid() {
		return ! empty( $this->data['pickup_address'] ) && ! empty( $this->data['dropoff_address'] );
	}

	/**
	 * Retrieve the tracking URL from the delivery data
	 *
	 * @return string|false Returns the URL if it exists, otherwise false
	 */
	public function get_tracking_url() {
		if ( array_key_exists( 'tracking_url', $this->data ) && ! empty( $this->data['tracking_url'] ) ) return $this->data['tracking_url'];
		else return false;
	}

	/**
	 * Get the configured lead time for orders
	 * Used in calculating pickup times and order windows
	 */
	public function get_lead_time() {
		$prefix = "woocommerce_doordash_";

		// Get the developer ID
		$lead_time = get_option( $prefix . 'lead_time' );

		if ( ! empty( $lead_time ) ) return intval( $lead_time ) * MINUTE_IN_SECONDS;
		else return 0;
	}

	/**
	 * Retrieve the estimated pickup time for the order.
	 *
	 * @return void
	 */
	public function get_pickup_time() {
		if ( array_key_exists( 'pickup_time_estimated', $this->data ) && ! empty( $this->data['pickup_time_estimated'] ) ) {
			return $this->data['pickup_time_estimated'];
		} else {
			return false;
		}
	}

	/**
	 * Get the locale for the delivery
	 *
	 * @param integer $user_id Optional user ID to pass to get_user_locale
	 * @return string Locale string
	 */
	public function get_locale( $user_id = 0 ) {
		return str_replace( '_', '-', get_user_locale( $user_id ) );
	}

}
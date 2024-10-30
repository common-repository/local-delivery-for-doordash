<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.inverseparadox.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/public
 * @author     Inverse Paradox <erik@inverseparadox.net>
 */
class Woocommerce_Doordash_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Doordash_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Doordash_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-doordash-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Doordash_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Doordash_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-doordash-public.js', array( 'jquery', 'selectWoo' ), $this->version, false );

	}

	/**
	 * Display the dropdown selector for users to choose a delivery location
	 *
	 * @param WC_Shipping_rate $shipping_rate
	 * @param $index
	 * @return void
	 */
	public function show_available_locations_dropdown( $shipping_rate, $index ) {
		// Get the selected method
		$chosen_shipping_rate_id = WC()->session->get( 'chosen_shipping_methods' )[0]; // [0]

		// Only output the field if the selected method is a WooCommerce DoorDash method
		if ( false !== strpos( $chosen_shipping_rate_id, 'woocommerce_doordash' ) && $shipping_rate->id === $chosen_shipping_rate_id ) {
			echo '<div class="wcdd-delivery-options">';

			// Get the enabled locations
			$locations = $this->get_enabled_locations();
			$selected_location = WC()->checkout->get_value( 'doordash_pickup_location' ) ? WC()->checkout->get_value( 'doordash_pickup_location' ) : WC()->session->get( 'doordash_pickup_location' );
			$location = new Woocommerce_Doordash_Pickup_Location( $selected_location );

			// Output pickup locations field
			woocommerce_form_field( 'doordash_pickup_location', array(
				'type' => 'select',
				'label' => __( 'Delivery From', 'local-delivery-by-doordash' ),
				'placeholder' => __( 'Select...', 'local-delivery-by-doordash' ),
				'class' => array( 'wcdd-pickup-location-select', 'update_totals_on_change' ), // add 'wc-enhanced-select'?
				'required' => true,
				'default' => $selected_location,
				'options' => $this->generate_locations_options( $locations ), // Use the enabled locations to generate an option array
			), $selected_location ); // $checkout->get_value( 'doordash_pickup_location' ) );

			if ( is_checkout() ) {
				// Output the schedule fields
				woocommerce_form_field( 'doordash_delivery_type', array(
					'type' => 'radio', // we need a conditional to check the plugin settings here -- only make it a radio if the delivery setting is set to both, otherwise it should be a hidden field
					'label' => __( 'Delivery Type', 'local-delivery-by-doordash' ),
					'class' => array( 'wcdd-delivery-type-select', 'update_totals_on_change' ),
					'required' => true,
					'default' => WC()->session->get( 'doordash_delivery_type' ) ? WC()->session->get( 'doordash_delivery_type' ) : 'immediate',
					'options' => array(
						'immediate' => __( 'ASAP', 'local-delivery-by-doordash' ),
						'scheduled' => __( 'Scheduled', 'local-delivery-by-doordash' ),
					),
				// ), WC()->checkout->get_value( 'doordash_delivery_type' ) );
				), WC()->session->get( 'doordash_delivery_type' ) ? WC()->session->get( 'doordash_delivery_type' ) : 'immediate' );

				if ( WC()->session->get( 'doordash_delivery_type' ) == 'scheduled' ) {
					echo '<div class="wcdd-delivery-schedule">';

						$delivery_days = $location->get_delivery_days();
						woocommerce_form_field( 'doordash_delivery_date', array(
							'type' => 'select',
							'label' => __( 'Day', 'local-delivery-by-doordash' ),
							'class' => array( 'wcdd-delivery-date-select', 'update_totals_on_change' ),
							'required' => true,
							'default' => WC()->session->get( 'doordash_delivery_date' ),
							'options' => $delivery_days,
						), WC()->checkout->get_value( 'doordash_delivery_date' ) );

						$selected_date = ! empty( WC()->session->get( 'doordash_delivery_date' ) ) ? WC()->session->get( 'doordash_delivery_date' ) : array_shift( array_keys( $delivery_days ) );
						woocommerce_form_field( 'doordash_delivery_time', array(
							'type' => 'select',
							'label' => __( 'Time', 'local-delivery-by-doordash' ),
							'class' => array( 'wcdd-delivery-time-select', 'update_totals_on_change' ),
							'required' => true,
							'default' => WC()->session->get( 'doordash_delivery_time' ),
							'options' => $location->get_delivery_times_for_date( $selected_date ),
						), WC()->checkout->get_value( 'doordash_delivery_time' ) );
					echo '</div>';
				} else {
					// If Immediate delivery is selected, display next available pickup time
					$delivery_time = $location->get_next_valid_time();
					if ( $delivery_time !== false ) {
						woocommerce_form_field( 'doordash_delivery_time', array(
							'type' => 'hidden',
							'default' => time(),
						), $delivery_time );
						$offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
						$delivery_time_local = intval( $delivery_time + $offset );
						echo '<p>' . date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $delivery_time_local ) . '</p>';
						// echo '<p>' . date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $delivery_time ) . '</p>';
					}
				}

				// Output the Dropoff Instructions field
				woocommerce_form_field( 'doordash_dropoff_instructions', array(
					'type' => 'text',
					'label' => __( 'Dropoff Instructions', 'local-delivery-by-doordash' ),
					'default' => WC()->session->get( 'doordash_dropoff_instructions' ),
				), WC()->checkout->get_value( 'doordash_dropoff_instructions' ) );

				// Only ouput the tip section if tipping is enabled
				if ( get_option( 'woocommerce_doordash_tipping' ) == 'enabled' ) {

					// Output tip selector field
					woocommerce_form_field( 'doordash_tip_select', array(
						'type' => 'radio',
						'label' => __( 'Tip', 'local-delivery-by-doordash' ),
						'class' => array( 'wcdd-tip-select', 'update_totals_on_change' ),
						'options' => $this->get_tip_options(),
						'default' => WC()->session->get( 'doordash_tip_select' ) ? WC()->session->get( 'doordash_tip_select' ) : apply_filters( 'wcdd_default_tip_option', '.20' ),
					), WC()->checkout->get_value( 'doordash_tip_select' ) );

					// Output the tip amount field
					woocommerce_form_field( 'doordash_tip_amount', array(
						'type' => WC()->session->get( 'doordash_tip_select' ) == 'other' ? 'number' : 'hidden',
						'label' => WC()->session->get( 'doordash_tip_select' ) == 'other' ? __( 'Custom Tip Amount', 'local-delivery-by-doordash' ) : '',
						'class' => array( 'wcdd-tip-amount', 'update_totals_on_change' ),
						'default' => WC()->session->get( 'doordash_tip_amount' ),
						'custom_attributes' => array(
							'min' => '0',
							'max' => PHP_INT_MAX,
						),
					), WC()->checkout->get_value( 'doordash_tip_amount' ) );

				} else {

					// Output hidden tip fields with zero values
					woocommerce_form_field( 'doordash_tip_select', array(
						'type' => 'hidden',
						'default' => 0,
					), 0 );

					woocommerce_form_field( 'doordash_tip_amount', array(
						'type' => 'hidden',
						'default' => 0,
					), 0 );

				}

			}


			woocommerce_form_field( 'doordash_external_delivery_id', array(
				// 'type' => 'text',
				'type' => 'hidden',
				'default' => WC()->session->get( 'doordash_external_delivery_id' ),
			), WC()->checkout->get_value( 'doordash_external_delivery_id' ) );

			if ( apply_filters( 'wcdd_show_doordash_logo', true ) ) {
				echo '<div class="wcdd-delivery-options-powered">';
					echo '<p>' . __( 'Powered By', 'local-delivery-by-doordash' ) . '</p>';
					echo '<img src="' . plugin_dir_url( __FILE__ ) . '/img/doordash-logo.svg" alt="DoorDash" />';
				echo '</div>';
			}

			echo '</div>';
		}
	}

	/**
	 * Validate the selected pickup location
	 *
	 * @return void
	 */
	public function validate_pickup_location() {
		$chosen_shipping_rate_id = WC()->session->get( 'chosen_shipping_methods' )[0];

		if ( false !== strpos( $chosen_shipping_rate_id, 'woocommerce_doordash' )
		&& empty( WC()->session->get( 'doordash_external_delivery_id' ) ) ) {
			wc_add_notice( __( 'Please choose a valid location.', 'local-delivery-by-doordash' ), 'error' );
		}
	}

	/**
	 * Adds a shipping phone number field to the shipping address
	 *
	 * @param array $fields Checkout fields
	 * @return array Filtered fields
	 */
	public function add_shipping_phone( $fields ) {
		$fields['shipping']['shipping_phone'] = apply_filters( 'wcdd_shipping_phone_field', array(
			'label' => __( 'Phone', 'woocommerce' ),
			'placeholder' => _x( 'Phone', 'placeholder', 'woocommerce' ),
			'type' => 'tel',
			'required' => true,
			'class' => array( 'form-row-wide', 'update_totals_on_change' ),
			'clear' => true,
			'validate' => array( 'phone' ),
			'autocomplete' => 'tel',
		) );
		return $fields;
	}

	/**
	 * Adds the update_totals_on_change class to the phone number field
	 *
	 * @param array $fields Checkout fields
	 * @return array Filtered fields
	 */
	public function add_update_totals_to_phone( $fields ) {
		$fields['billing']['billing_phone']['class'][] = 'update_totals_on_change';
		return $fields;
	}

	/**
	 * Save the pickup location as order meta
	 *
	 * @param WC_Order $order WooCommerce order that was created
	 * @param $data Order data?
	 */
	public function save_pickup_location_to_order( $order, $data ) {
		if ( isset( $_REQUEST['doordash_pickup_location'] ) && ! empty( $_REQUEST['doordash_pickup_location'] ) ) {
			$order->update_meta_data( 'doordash_pickup_location', intval( $_REQUEST['doordash_pickup_location'] ) );
		}
	}

	/**
	 * Save the pickup location as shipping item meta
	 *
	 * @param WC_Order_Item $item Current order item
	 * @param string $package_key Array key of the current package
	 * @param array $package Array of package data
	 * @param WC_Order $order Current order
	 * @return void
	 */
	public function save_pickup_location_to_order_item_shipping( $item, $package_key, $package, $order ) {
		if ( isset( $_REQUEST['doordash_pickup_location'] ) && ! empty( $_REQUEST['doordash_pickup_location'] ) ) {
			$item->update_meta_data( '_doordash_pickup_location', intval( $_REQUEST['doordash_pickup_location'] ) );
		}
	}

	public function display_pickup_location_on_order_item_totals( $total_rows, $order, $tax_display ) {
		// Get the selected pickup location
		$doordash_pickup_location = $order->get_meta( 'doordash_pickup_location' );
		$new_total_rows = array();

		// Bail if location not set
		if ( empty( $doordash_pickup_location ) ) return $total_rows;

		// Set up the location object
		$location = new Woocommerce_Doordash_Pickup_Location( intval( $doordash_pickup_location ) );

		foreach( $total_rows as $key => $value ) {
			if ( 'shipping' == $key ) {
				// Add the row with information on the pickup location
				$new_total_rows[ 'doordash_pickup_location' ] = array(
					'label' => __( 'DoorDash from:', 'local-delivery-by-doordash' ),
					'value' => $location->get_name() . '<br>' . $location->get_formatted_address(),
				);
			} else {
				$new_total_rows[ $key ] = $value;
			}
		}

		// Return the modified rows
		return $new_total_rows;
	}

	/**
	 * Retrieve an array of Pickup Location objects that are currently enabled
	 *
	 * @return array Array of Woocommerce_Doordash_Pickup_Location objects
	 */
	public function get_enabled_locations() {
		// Set up the query, allow filtering the query arguments
		$args = apply_filters( 'wcdd_enabled_locations_query_args', array(
			'post_type' => 'dd_pickup_location',
			'post_status' => array( 'publish' ), // Post status publish are enabled
			'numposts' => -1,
		) );
		$locations = get_posts( $args );

		foreach ( $locations as &$location ) {
			$location = new Woocommerce_Doordash_Pickup_Location( $location ); // Set the array with the location object instead of the post
		}

		return apply_filters( 'wcdd_enabled_locations', $locations );
	}

	/**
	 * Gets the tip percentages to show on checkout page
	 *
	 * @return array Keys are strings with a decimal representation of the discount, values are the labels to display for the buttons
	 */
	public function get_tip_options() {
		return apply_filters( 'wcdd_tip_options', array(
			'.15' => '15%',
			'.20' => '20%',
			'.25' => '25%',
			'other' => 'Custom',
		) );
	}

	/**
	 * Create ID => Name array of available locations
	 *
	 * @param array $locations Array of Woocommerce_Doordash_Pickup_Location objects
	 * @return array New array with ID => Name
	 */
	public function generate_locations_options( $locations ) {
		// Add a placeholder option
		$options = array( 0 => __( 'Select', 'local-delivery-by-doordash' ) );

		foreach( $locations as $location ) {
			$options[ $location->get_id() ] = apply_filters( 'wcdd_location_option_name', $location->get_name() . ' - ' . $location->get_formatted_address(), $location );
		}

		return $options;
	}

	/**
	 * Saves the pickup location, tip select, and tip amount to the session on update_order_review
	 *
	 * @param string $data String with passed data from checkout
	 * @return void
	 */
	public function save_data_to_session( $data ) {

		// Parse the data from a string to an array
		parse_str( $data, $data );

		// Save the Pickup Location
		if ( array_key_exists( 'doordash_pickup_location', $data ) ) { // phpcs:ignore String is parsed to array
			$doordash_pickup_location = $data['doordash_pickup_location'];
			WC()->session->set( 'doordash_pickup_location', $doordash_pickup_location );
		}

		// Save the dropoff instructions
		if ( array_key_exists( 'doordash_dropoff_instructions', $data ) ) { // phpcs:ignore
			$doordash_dropoff_instructions = $data['doordash_dropoff_instructions'];
			WC()->session->set( 'doordash_dropoff_instructions', $doordash_dropoff_instructions );
		}

		// Save the delivery type
		if ( array_key_exists( 'doordash_delivery_type', $data ) ) { // phpcs:ignore
			$doordash_delivery_type = $data['doordash_delivery_type'];
			WC()->session->set( 'doordash_delivery_type', $doordash_delivery_type );
		}

		// Save the delivery date
		if ( array_key_exists( 'doordash_delivery_date', $data ) ) { // phpcs:ignore
			$doordash_delivery_date = $data['doordash_delivery_date'];
			WC()->session->set( 'doordash_delivery_date', $doordash_delivery_date );
		}

		// Save the delivery time
		if ( array_key_exists( 'doordash_delivery_time', $data ) ) { //phpcs:ignore
			$doordash_delivery_time = $data['doordash_delivery_time'];
			WC()->session->set( 'doordash_delivery_time', $doordash_delivery_time );
		}

		// Save the selected tip value
		if ( array_key_exists( 'doordash_tip_select', $data ) ) { // phpcs:ignore
			$doordash_tip_select = $data['doordash_tip_select'];
			WC()->session->set( 'doordash_tip_select', $doordash_tip_select );
		} else {
			$doordash_tip_select = 'other';
		}

		// Handle the actual tip amount from the options or the number input
		if ( 'other' != $doordash_tip_select ) {
			$doordash_tip_amount = WC()->cart->get_subtotal() * floatval( $doordash_tip_select );
		} elseif ( array_key_exists( 'doordash_tip_amount', $data ) ) { // phpcs:ignore
			// Save the entered tip amount
			$doordash_tip_amount = floatval( $data['doordash_tip_amount'] );
		} else {
			$doordash_tip_amount = 0;
		}
		WC()->session->set( 'doordash_tip_amount', number_format( $doordash_tip_amount, 2, '.', '' ) );

		return;
	}

	/**
	 * Update the user selected delivery pickup location in the user's session
	 *
	 * @return void
	 */
	public function save_pickup_location_to_session() {
		// Bail if the post data isn't set
		if ( ! array_key_exists( 'location_id', $_POST ) || empty( $_POST['location_id'] ) ) exit;

		// Sanitize
		$location_id = intval( $_POST['location_id'] );

		// Set the location ID in the session
		WC()->session->set( 'doordash_pickup_location', $location_id );
		exit;
	}

}

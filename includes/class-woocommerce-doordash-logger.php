<?php

/**
 * DoorDash Logger
 *
 * @link       https://www.inverseparadox.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/includes
 */

/**
 * DoorDash Logger
 *
 * Enables streamlined access to the WC_Logger class. 
 * Automatically sets the context for the plugin. 
 * Automatically runs input through wc_print_r for output
 * Access using the same methods as WC_Logger
 * Example: WCDD()->log->debug( "Hello World" );
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/includes
 * @author     Inverse Paradox <erik@inverseparadox.net>
 */
class Woocommerce_Doordash_Logger {

	/**
	 * WooCommerce logging object
	 *
	 * @var WC_Logger
	 */
	public $logger;

	/**
	 * Context for logs
	 * 
	 * @var array
	 */
	public $context = array(
		'source' => 'local-delivery-by-doordash'
	);

	/**
	 * Log levels for WooCommerce that we should handle
	 *
	 * @var array
	 */
	private $levels = array(
		'emergency',
		'alert',
		'critical',
		'error',
		'warning',
		'notice',
		'info',
		'debug',
	);

	/**
	 * Instantiate logger
	 */
	public function __construct() {
		$this->logger = wc_get_logger();
	}

	/**
	 * Magic caller to map to the WC_Logger class
	 * This method intercepts nonexistent methods
	 * If the method is a WooCommerce log level, it logs the message
	 *
	 * @param string $method Name of the log level
	 * @param array $args Array of arguments. First is the message
	 * @return void
	 */
	public function __call( $level, $args ) {
		if ( in_array( $level, $this->levels ) ) {
			$message = wc_print_r( array_shift( $args ), true );
			$this->logger->log( $level, $message, $this->context );
		}
	}
	
}
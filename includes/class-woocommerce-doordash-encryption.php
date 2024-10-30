<?php

/**
 * DoorDash Encryption
 *
 * @link       https://www.inverseparadox.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/includes
 */

/**
 * DoorDash Encryption
 *
 * Contains functionality encrypt and decrypt secret keys
 *
 * @package    Woocommerce_Doordash
 * @subpackage Woocommerce_Doordash/includes
 * @author     Inverse Paradox <erik@inverseparadox.net>
 */
class Woocommerce_Doordash_Encryption {
	/**
	 * Encrypt sensitive data before storing in the database
	 *
	 * @param string $value The new, unserialized option value
	 * @param string $old_value The old option value
	 * @param string $option The option name (the dynamic part of the hook name)
	 * @return string Encrypted string
	 */
	public function encrypt_meta( $value, $old_value, $option ) {
		return $this->encrypt( $value );
	}

	/**
	 * Decrypt the signing secret when reading the option from the database
	 *
	 * @param string $value The value read from the database
	 * @param string $option Option name (dynamic part of the hook name)
	 * @return void
	 */
	public function decrypt_meta( $value, $option ) {
		return $this->decrypt( $value );
	}

	/**
	 * Decrypt the encrypted options when they are first retrieved
	 *
	 * @param array $alloptions Array of all site options
	 * @return array Filtered array with our values decrypted
	 */
	public function get_all_options( $alloptions ) {

		// The option names that need to be decrypted
		$decrypt = array(
			'woocommerce_doordash_production_signing_secret',
			'woocommerce_doordash_sandbox_signing_secret',
			'woocommerce_doordash_production_key_id',
			'woocommerce_doordash_sandbox_key_id',
		);

		// Loop through the option names
		foreach( $decrypt as $key ) {
			if ( array_key_exists( $key, $alloptions ) ) {
				// If the option exists, decrypt it and store back to the array
				$alloptions[$key] = $this->decrypt( $alloptions[$key] );
			}
		}

		// Return the options
		return $alloptions;
	}

	/**
	 * Encrypt data using AES-256-CBC
	 *
	 * @param string $value String to encrypt
	 * @return string Encrypted string
	 */
	public function encrypt( $value ) {
			// Set default output value
			$output = null;

			// We are encrypting
			$output = base64_encode( openssl_encrypt($value, "AES-256-CBC", $this->get_key(), 0, $this->get_iv() ) );

			// Return the final value
			return $output;
		}
		
	/**
	 * Decrypt data from AES-256-CBC
	 *
	 * @param string $value String to decrypt
	 * @return string Decrypted string
	 */
	public function decrypt( $value ) {
			// Set default output value
			$output = null;
			// We are decrypting
			$output = openssl_decrypt(base64_decode( $value ), "AES-256-CBC", $this->get_key(), 0, $this->get_iv() );

			// Return the decrypted text
			return $output;
	}

	/**
	 * Get the encryption key to use for encryption/decryption
	 *
	 * @return string Hashed key
	 */
	protected function get_key() {
		if ( defined( 'WCDD_ENCRYPTION_KEY' ) ) $key = WCDD_ENCRYPTION_KEY;
		else if ( defined( 'AUTH_KEY' ) ) $key = AUTH_KEY;
		else $key = 'O#F+ICSJ=kpy._W+@g{eEP;6G^S`.wAoOd_rMpsqj},eZ7g@k93j0e&;u4iG=nh,';

		return hash( 'sha256', $key );
	}

	/**
	 * Get the encryption initialization vector to use for encryption/decryption
	 *
	 * @return string Hashed IV of the correct length
	 */
	protected function get_iv() {
		if ( defined( 'WCDD_ENCRYPTION_IV' ) ) $iv = WCDD_ENCRYPTION_IV;
		else if ( defined( 'AUTH_SALT' ) ) $iv = AUTH_SALT;
		else $iv = 'n&l@7:1p1Y-,:xV|J-c?m0DH-:[_<MW2J}}YVpny^6-Y+Ai0Z8Gyy=Q~/k M/$.R';

		return substr( hash( 'sha256', $iv ), 0, 16 );
	}

	/**
	 * Dumb function to check if a key is already encrypted
	 *
	 * @param string $string String to check
	 * @return boolean Encrypted status
	 */
	public function is_encrypted( $string ) {
		// if ( str_ends_with( $string, '==' ) ) return true;
		if ( substr( $string, -2 ) == '==' ) return true;
		return false;
	}
}
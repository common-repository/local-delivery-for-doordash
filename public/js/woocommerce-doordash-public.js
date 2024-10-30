(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	// Run on DOM ready
	$(function() {
		/**
		 * Check if a node is blocked for processing.
		 *
		 * @param {JQuery Object} $node
		 * @return {bool} True if the DOM Element is UI Blocked, false if not.
		 */
		var is_blocked = function( $node ) {
			return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
		};

		/**
		 * Block a node visually for processing.
		 *
		 * @param {JQuery Object} $node
		 */
		var block = function( $node ) {
			if ( ! is_blocked( $node ) ) {
				$node.addClass( 'processing' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );
			}
		};

		/**
		 * Unblock a node after processing is complete.
		 *
		 * @param {JQuery Object} $node
		 */
		var unblock = function( $node ) {
			$node.removeClass( 'processing' ).unblock();
		};


		// Enhanced select on location dropdown
		// Need to make this fire when the shipping method is selected/updated as well
		// $('#doordash_pickup_location').selectWoo();

		// Add tabindex to tip radio labels for accessibility
		//!! This needs attention as it is not currently working, if we can add the attribute to the label, then we can remove this
		var $tipRadioLabel = $('.wc-doordash-location.hours .wc-doordash-location__tip-radio-label');
		$('body.woocommerce-checkout').each($tipRadioLabel, function() {
			$(this).attr('tabindex', '0');
		});

		// Updates session when changing pickup location on cart
		$('body.woocommerce-cart').on( 'change', '#doordash_pickup_location', function() {
			block( $('.cart_totals') );
			$.ajax({
				type: 'POST',
				url: woocommerce_params.ajax_url,
				data: {
					"action": "wcdd_update_pickup_location", 
					"location_id":this.value
				},
				success: function( data ) {
					$(document).trigger('wc_update_cart');
				},
				fail: function( data ) {
					unblock( $('.cart_totals') );
				}
			});
		} );

	});

	/**
	 * Adds mobile classes to containers based on the width of the shipping method container
	 */
	function mobileViews () {
		var $wcDoorDashShippingContainer = $( "tr.woocommerce-shipping-totals.shipping td" ).width();
		// console.log( $( $wcDoorDashShippingContainer ) );
		if ( $wcDoorDashShippingContainer < 195 && $wcDoorDashShippingContainer > 155 ) {
			// if the width of the shipping container is less than 195px and greater than 155px, then add the class to the options container
			$('.wcdd-delivery-options').addClass('mobile-view');
		} else if ( $wcDoorDashShippingContainer < 195 && $wcDoorDashShippingContainer < 155 ) {
			// if the width of the shipping container is less than 155px and 195px, then remove the class to the options container
			$('.wcdd-delivery-options').addClass('tiny-view');
		} else if ( $wcDoorDashShippingContainer > 155 && $wcDoorDashShippingContainer > 195 ) {
			// if the width of the shipping container is greater than 155px and less than 195px, then remove the class to the options container
			$('.wcdd-delivery-options').removeClass('tiny-view');
		} else if ( $wcDoorDashShippingContainer > 195 ) {
			// if the width of the shipping container is greater than 195px, then add the class to the options container
			$('.wcdd-delivery-options').removeClass('mobile-view');
		}
	}

	$(window).ajaxComplete(function( event, request, settings ) {
		mobileViews();
	});

	$(window).on('resize load', function() {
		mobileViews();
	});

})( jQuery );

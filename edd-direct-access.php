<?php
/*
 * Plugin Name:       Easy Digital Downloads - Direct Access
 * Plugin URI:        https://github.com/shazzad/edd-direct-access/
 * Description:       Enables an email direct link to EDD purchase confirmation page, without restriction.
 * Version:           1.0.0
 * Author:            Shazzad Hossain Khan
 * Author URI:        https://shazzad.me
 * License:           GPLv2
 * Requires at least: 5.5.3
 * Requires PHP:      7.2
 * Text Domain:       edd-direct-access
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'EDD_Direct_Access' ) ) {

	/**
	 * Main EDD_Direct_Access class
	 */
	class EDD_Direct_Access {

		private static $instance;

		public static $param_name = 'edd_direct_access_key';

		/**
		 * Get active instance
		 *
		 * @access      public
		 * @return      object self::$instance The one true EDD_Direct_Access
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new EDD_Direct_Access();
				self::$instance->register_hooks();
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 */
		private function register_hooks() {
			add_action( 'template_redirect', array( $this, 'auth_handler' ) );
			add_filter( 'edd_email_tags', array( $this, 'email_tags' ) );

			add_filter( 'edd_settings_sections_extensions', array( $this, 'add_settings_section' ) );
			add_filter( 'edd_settings_extensions', array( $this, 'register_settings' ) );
		}

		/**
		 * Authenicates direct access
		 */
		public function auth_handler() {

			if ( empty( $_REQUEST[ self::$param_name ] ) ) {
				return;
			}

			$success_page = edd_get_option( 'success_page', '' );
			if ( ! $success_page || ! is_page( $success_page ) ) {
				return;
			}

			$purchase_key = wp_unslash( $_REQUEST[ self::$param_name ] );

			$session = edd_get_purchase_session();
			if ( isset( $session['purchase_key'] ) && $purchase_key === $session['purchase_key'] ) {
				wp_redirect( remove_query_arg( array( self::$param_name ) ) );
				return;
			}

			$purchase_id = edd_get_purchase_id_by_key( $_REQUEST[ self::$param_name ] );
			if ( ! $purchase_id ) {
				wp_redirect( remove_query_arg( array( self::$param_name ) ) );
				return;
			}

			$purchase_data = array( 'purchase_key' => $purchase_key );

			edd_set_purchase_session( $purchase_data );

			wp_redirect( remove_query_arg( array( self::$param_name ) ) );
			exit;
		}

		public function email_tags( $tags ) {
			$tags[] = array(
				'tag'         => 'direct_access_link',
				'description' => __( 'Purchase Confirmation Direct Access Link' ),
				'function'    => array( $this, 'direct_access_link_callback' )
			);

			return $tags;
		}

		public function direct_access_link_callback( $payment_id ) {

			$success_page = edd_get_option( 'success_page', '' );
			if ( ! get_post( $success_page ) || ! edd_get_payment_key( $payment_id ) ) {
				return '';
			}

			$direct_access_link = add_query_arg( 
				array(
					self::$param_name => edd_get_payment_key( $payment_id ),
				), 
				get_permalink( $success_page )
			);

			$email_link_text = edd_get_option( 'edd_direct_access_email_link_text', __( 'Purchase Confirmation', 'edd-direct-access' ) );

			if ( 'none' === edd_get_option( 'email_template' ) || empty( $email_link_text ) ) {
				return $direct_access_link;
			} else {
				return '<a href="' . esc_url( $direct_access_link ) . '">' . $email_link_text . '</a>';
			}
		}

		/**
		 * Add settings section
		 *
		 * @since       1.0.0
		 * @param       array $sections The existing extensions sections
		 * @return      array The modified extensions settings
		 */
		function add_settings_section( $sections ) {
			$sections['direct-access'] = __( 'Direct Access', 'edd-direct-access' );

			return $sections;
		}

		/**
		 * Add settings
		 *
		 * @since       1.0.0
		 * @param       array $settings the existing plugin settings
		 * @return      array
		 */
		function register_settings( $settings ) {
			$new_settings = array(
				'direct-access' => array(
					array(
						'id'   => 'edd_direct_access_general_settings',
						'name' => '<strong>' . __( 'Direct Access Settings', 'edd-upload-image' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),
					array(
						'id'   => 'edd_direct_access_email_link_text',
						'name' => __( 'Email link text', 'edd-upload-image' ),
						'desc' => __( '', 'edd-upload-image' ),
						'type' => 'text',
						'std'  => __( 'Purchase Confirmation', 'edd-direct-access' )
					),
				)
			);

			return array_merge( $settings, $new_settings );
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_Direct_Access
 * instance to functions everywhere
 *
 * @return      \EDD_Direct_Access The one true EDD_Direct_Access
 */
function edd_direct_access() {
	if ( defined( 'EDD_VERSION' ) ) {
		return EDD_Direct_Access::instance();
	}
}
add_action( 'plugins_loaded', 'edd_direct_access' );

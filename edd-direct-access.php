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

function edd_direct_access_auth_handler() {
	
	if ( empty( $_REQUEST['edd_direct_access_key'] ) ) {
		return;
	}

	$success_page = edd_get_option( 'success_page', '' );
	if ( ! $success_page || ! is_page( $success_page ) ) {
		return;
	}

	$purchase_key = wp_unslash( $_REQUEST['edd_direct_access_key'] );
	
	$session = edd_get_purchase_session();
	if ( isset( $session['purchase_key'] ) && $purchase_key === $session['purchase_key'] ) {
		wp_redirect( remove_query_arg( array( 'edd_direct_access_key' ) ) );
		
		return;
	}
	
	$purchase_id = edd_get_purchase_id_by_key( $_REQUEST['edd_direct_access_key'] );
	if ( ! $purchase_id ) {
		wp_redirect( remove_query_arg( array( 'edd_direct_access_key' ) ) );

		return;
	}

	$purchase_data = array( 'purchase_key' => $purchase_key );

	edd_set_purchase_session( $purchase_data );
	
	wp_redirect( remove_query_arg( array( 'edd_direct_access_key' ) ) );

	exit;
}
add_action( 'template_redirect', 'edd_direct_access_auth_handler' );


function edd_direct_access_email_tags( $tags ) {
	$tags[] = array(
		'tag'         => 'direct_access_link',
		'description' => __( 'Purchase Confirmation Direct Access Link' ),
		'function'    => 'edd_direct_access_link_callback'
	);
	
	return $tags;
}
add_filter( 'edd_email_tags', 'edd_direct_access_email_tags' );


function edd_direct_access_link_callback( $payment_id ) {
	$success_page = edd_get_option( 'success_page', '' );
	if ( ! get_post( $success_page ) ) {
		return '';
	}
	
	$url = get_permalink( $success_page );
	
	$direct_access_link = esc_url( add_query_arg( array(
		'edd_direct_access_key' => edd_get_payment_key( $payment_id ),
	), get_permalink( $success_page ) ) );
	
	$formatted = sprintf( __( '%1$sPurchase Confirmation%2$s' ), '<a href="' . $direct_access_link . '">', '</a>' );
	
	if ( edd_get_option( 'email_template' ) !== 'none' ) {
		return $formatted;
	} else {
		return $receipt_url;
	}
}

function edd_direct_access_p( $data = array(), $exit = false ) {
	echo '<pre>';
	print_r( $data );
	echo '</pre>';
	
	if ( $exit ) {
		exit;
	}
}
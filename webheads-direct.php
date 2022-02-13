<?php
/*
 * Plugin Name:       Webheads Direct
 * Plugin URI:        https://github.com/shazzad/webheads-direct/
 * Description:       Enables an email direct link to EDD purchase confirmation page, without restriction.
 * Version:           0.0.1
 * Author:            Shazzad Hossain Khan
 * Author URI:        https://shazzad.me
 * License:           GPLv2
 * Requires at least: 5.5.3
 * Requires PHP:      7.2
 * Text Domain:       webheads-direct
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', function () {
	
	if ( empty( $_REQUEST['webheads_direct_key'] ) ) {
		return;
	}
	
	$success_page = edd_get_option( 'success_page', '' );
	if ( ! $success_page || ! is_page( $success_page ) ) {
		return;
	}
	
	$purchase_key = wp_unslash( $_REQUEST['webheads_direct_key'] );
	
	$session = edd_get_purchase_session();
	if ( isset( $session['purchase_key'] ) && $purchase_key === $session['purchase_key'] ) {
		wp_redirect( remove_query_arg( array( 'webheads_direct_key' ) ) );
		
		return;
	}
	
	$purchase_id = edd_get_purchase_id_by_key( $_REQUEST['webheads_direct_key'] );
	if ( ! $purchase_id ) {
		wp_redirect( remove_query_arg( array( 'webheads_direct_key' ) ) );
		
		return;
	}
	
	$purchase_data = array(
		'purchase_key' => $purchase_key
	);
	edd_set_purchase_session( $purchase_data );
	
	wp_redirect( remove_query_arg( array( 'webheads_direct_key' ) ) );
	exit;
} );

add_filter( 'edd_email_tags', function ( $tags ) {
	$tags[] = array(
		'tag'         => 'purchase_confirmation',
		'description' => __( 'Purchase Confirmation Page Link' ),
		'function'    => 'webheads_direct_purchase_confirmation'
	);
	
	return $tags;
} );

function webheads_direct_purchase_confirmation( $payment_id ) {
	$success_page = edd_get_option( 'success_page', '' );
	if ( ! get_post( $success_page ) ) {
		return '';
	}
	
	$url = get_permalink( $success_page );
	
	$purchase_confirmation = esc_url( add_query_arg( array(
		'webheads_direct_key' => edd_get_payment_key( $payment_id ),
	), get_permalink( $success_page ) ) );
	
	$formatted = sprintf( __( '%1$sPurchase Confirmation%2$s' ), '<a href="' . $purchase_confirmation . '">', '</a>' );
	
	if ( edd_get_option( 'email_template' ) !== 'none' ) {
		return $formatted;
	} else {
		return $receipt_url;
	}
}

function webheads_direct_p( $data = array(), $exit = false ) {
	echo '<pre>';
	print_r( $data );
	echo '</pre>';
	
	if ( $exit ) {
		exit;
	}
}

function webheads_add_text_message() {
	?>
    <fieldset id="edd_checkout_text_message">
        <legend><?php echo esc_html__( 'Message', 'webheads-direct' ); ?></legend>
        <p id="edd-text-msg-wrap">
            <label class="edd-label" for="edd-upload-message"><?php echo esc_html__( 'Upload Message', 'webheads-direct' ); ?></label>
            <span class="edd-description" id="edd-upload-message-description"><?php echo esc_html__( 'This message is for upload images.', 'webheads-direct' ); ?></span>
            <textarea name="edd-upload-message" id="edd-upload-message" class="edd-input" cols="30" rows="10"></textarea>
        </p>
    </fieldset>
	<?php
	
}

add_action( 'edd_checkout_form_top', 'webheads_add_text_message' );

function webheads_save_custom_fields( $payment_meta ) {
	if ( isset( $_POST['edd-upload-message'] ) && ! empty( $_POST['edd-upload-message'] ) ) {
		$payment_meta['edd_upload_message'] = sanitize_textarea_field( $_POST['edd-upload-message'] );
	}
	
	return $payment_meta;
}

add_action( 'edd_payment_meta', 'webheads_save_custom_fields' );

function webheads_upload_message( $payment_id ) {
	$get_payment_meta = get_post_meta( $payment_id, '_edd_payment_meta', true );
	
	?>
    <div id="edd-upload-notes" class="postbox">
        <h3 class="hndle"><?php _e( 'Upload Messages', 'webheads-direct' ); ?></h3>
        <div class="inside">
            <textarea name="edd-upload-message" id="edd-upload-message" class="large-text"><?php echo ! empty( $get_payment_meta['edd_upload_message'] ) ? $get_payment_meta['edd_upload_message'] : '' ?></textarea>
        </div>
    </div>
	<?php
}

add_action( 'edd_view_order_details_main_after', 'webheads_upload_message' );
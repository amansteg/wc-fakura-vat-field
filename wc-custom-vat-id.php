<?php
/**
 * Plugin Name: WooCommerce Custom Fakura VAT
 * Description: Adds a Faktura VAT ID (NIP Number) field to the WooCommerce checkout page, saves it, and includes it in the REST API responses.
 * Version:     1.0
 * Author:      Aman Joshi
 * Text Domain: woocommerce-custom-fakura-vat
 * WC requires at least: 3.0
 * WC tested up to: 5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add the VAT ID field to the checkout page
add_filter('woocommerce_checkout_fields', 'add_nip_number_checkout_field');
function add_nip_number_checkout_field($fields) {
    $fields['billing']['nip_number'] = array(
        'label'       => __('Faktura VAT', 'woocommerce'),
        'placeholder' => _x('Numer NIP', 'placeholder', 'woocommerce'),
        'required'    => false,
        'class'       => array('form-row-wide'),
        'clear'       => true,
		'maxlength'   => 11,
        'custom_attributes' => array(
            'pattern'   => '^[a-zA-Z0-9]*$',  // Only alphanumeric characters
            'title'     => 'Only alphanumeric characters are allowed.'
        )
    );

    return $fields;
}

// Save the VAT ID when the checkout form is processed
add_action('woocommerce_checkout_update_order_meta', 'save_nip_number_to_order');
function save_nip_number_to_order($order_id) {
    if (!empty($_POST['nip_number'])) {
        update_post_meta($order_id, '_nip_number', sanitize_text_field($_POST['nip_number']));
    }
}

// Include the VAT ID in the REST API response
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_nip_number_to_api_response', 10, 3);
function add_nip_number_to_api_response($response, $order, $request) {
    $nip_number = get_post_meta($order->get_id(), '_nip_number', true);
    if (empty($nip_number)) {
        $nip_number = null; // Set to null if empty
    }
    $response->data['nip_number'] = $nip_number;
    return $response;
}

//RegEx for NIP Number
add_action('woocommerce_checkout_process', 'validate_faktura_vat_field');
function validate_faktura_vat_field() {
    if (isset($_POST['nip_number']) && !preg_match('/^[a-zA-Z0-9]{0,11}$/', $_POST['nip_number'])) {
        wc_add_notice(__('Wprowadź prawidłowy numer NIP.', 'woocommerce'), 'error');
    }
}


add_filter('woocommerce_order_formatted_billing_address', 'add_nip_number_to_admin_billing_address', 10, 2);
function add_nip_number_to_admin_billing_address($address, $order) {
    $nip_number = get_post_meta($order->get_id(), '_nip_number', true);
    if (!empty($nip_number)) {
        $address['nip_number'] = __('NIP Number:', 'woocommerce') . ' ' . $nip_number;
    }
    return $address;
}

add_filter('woocommerce_localisation_address_formats', 'add_nip_number_to_address_format');
function add_nip_number_to_address_format($formats) {
    foreach ($formats as $key => $format) {
        $formats[$key] .= "\n{nip_number}";
    }
    return $formats;
}

add_filter('woocommerce_formatted_address_replacements', 'add_nip_number_to_formatted_address', 10, 2);
function add_nip_number_to_formatted_address($replacements, $args) {
    $replacements['{nip_number}'] = isset($args['nip_number']) ? $args['nip_number'] : '';
    return $replacements;
}


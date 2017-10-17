<?php
   /*
   Plugin Name: WP-CRM System Contact From User
   Plugin URI: https://www.wp-crm.com
   Description: Create a contact in WP-CRM System from an existing user account.
   Version: 2.0.5
   Author: Scott DeLuzio
   Author URI: https://www.wp-crm.com
   Text Domain: wp-crm-system-contact-user
   */

	/*  Copyright 2015  Scott DeLuzio (email : support (at) wp-crm.com)	*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define('WPCRM_CONTACT_FROM_USER', __FILE__);
define('WPCRM_CONTACT_FROM_USER_VERSION', '2.0.5' );

/* Start Updater */
if (!defined('WPCRM_BASE_STORE_URL')){
	define( 'WPCRM_BASE_STORE_URL', 'http://wp-crm.com' );
}
// the name of your product. This should match the download name in EDD exactly
define( 'WPCRM_CONTACT_FROM_USER_NAME', 'Contact From User' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

if( !class_exists( 'WPCRM_SYSTEM_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}

function wpcrm_contact_from_user_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'wpcrm_contact_from_user_license_key' ) );

	// setup the updater
	$edd_updater = new WPCRM_SYSTEM_SL_Plugin_Updater( WPCRM_BASE_STORE_URL, __FILE__, array(
			'version' 	=> WPCRM_CONTACT_FROM_USER_VERSION, // current version number
			'license' 	=> $license_key, 					// license key (used get_option above to retrieve from DB)
			'item_name' => WPCRM_CONTACT_FROM_USER_NAME, 	// name of this plugin
			'author' 	=> 'Scott DeLuzio'  				// author of this plugin
		)
	);

}
add_action( 'admin_init', 'wpcrm_contact_from_user_updater', 0 );

function wpcrm_contact_from_user_register_option() {
	// creates our settings in the options table
	register_setting('wpcrm_license_group', 'wpcrm_contact_from_user_license_key', 'wpcrm_contact_from_user_sanitize_license' );
}
add_action('admin_init', 'wpcrm_contact_from_user_register_option');

function wpcrm_contact_from_user_sanitize_license( $new ) {
	$old = get_option( 'wpcrm_contact_from_user_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'wpcrm_contact_from_user_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}
function wpcrm_contact_from_user_activate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['wpcrm_contact_from_user_activate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'wpcrm_contact_from_user_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( WPCRM_CONTACT_FROM_USER_NAME ), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPCRM_BASE_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"

		update_option( 'wpcrm_contact_from_user_license_status', $license_data->license );

	}
}
add_action('admin_init', 'wpcrm_contact_from_user_activate_license');

function wpcrm_contact_from_user_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['wpcrm_contact_from_user_deactivate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'wpcrm_contact_from_user_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode( WPCRM_CONTACT_FROM_USER_NAME ), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPCRM_BASE_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' )
			delete_option( 'wpcrm_contact_from_user_license_status' );

	}
}
add_action('admin_init', 'wpcrm_contact_from_user_deactivate_license');
/* End Updater */

/* Load Text Domain */
add_action('plugins_loaded', 'wp_crm_contact_user_plugin_init');
function wp_crm_contact_user_plugin_init() {
	load_plugin_textdomain( 'wp-crm-system-contact-user', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
/**
 * A function used to programmatically create a contact from a user account in WordPress. The slug, author ID, and title
 * are defined within the context of the function.
 *
 * @returns -1 if the post was never created, -2 if a post with the same title exists, or the ID
 *          of the post if successful.
 */
function wpcrm_system_programmatically_create_contact() {
	// Make sure add contact link was clicked.
	if( isset($_GET['action']) && $_GET['action'] == 'wpcrm_add_contact_user') {
		if( isset($_GET['user'])) {
			$userID = $_GET['user'];
		} else {
			$userID = 0;
		}
		$user_info = get_userdata($userID);

		// Initialize the page ID to -1. This indicates no action has been taken.
		$post_id = -1;

		// Setup the author, slug, title, and contact information.
		$author_id 		= get_current_user_id();
		$userFirstName 	= $user_info->first_name;
		$userLastName 	= $user_info->last_name;
		$userEmail 		= $user_info->user_email;
		$userURL 		= $user_info->user_url;
		$slug 			= preg_replace("/[^A-Za-z0-9]/",'',strtolower($userFirstName)) . '-' . preg_replace("/[^A-Za-z0-9]/",'',strtolower($userLastName));
		$title 			= $userFirstName . ' ' . $userLastName;
		$userAddress1 	= $user_info->billing_address_1;
		$userAddress2 	= $user_info->billing_address_2;
		$userCity 		= $user_info->billing_city;
		$userState 		= $user_info->billing_state;
		$userZip 		= $user_info->billing_postcode;
		$userPhone 		= $user_info->billing_phone;


		// If the page doesn't already exist, then create it
		if( null == get_page_by_title( $title, OBJECT, 'wpcrm-contact' ) ) {

			// Set the post ID so that we know the post was created successfully
			$post_id = wp_insert_post(
				array(
					'comment_status'	=>	'closed',
					'ping_status'		=>	'closed',
					'post_author'		=>	$author_id,
					'post_name'			=>	$slug,
					'post_title'		=>	$title,
					'post_status'		=>	'publish',
					'post_type'			=>	'wpcrm-contact'
				)
			);
			//Add user's information to contact fields.
			add_post_meta($post_id,'_wpcrm_contact-first-name',$userFirstName,true);
			add_post_meta($post_id,'_wpcrm_contact-last-name',$userLastName,true);
			add_post_meta($post_id,'_wpcrm_contact-email',$userEmail,true);
			add_post_meta($post_id,'_wpcrm_contact-website',$userURL,true);
			add_post_meta($post_id,'_wpcrm_contact-address1',$userAddress1,true);
			add_post_meta($post_id,'_wpcrm_contact-address2',$userAddress2,true);
			add_post_meta($post_id,'_wpcrm_contact-city',$userCity,true);
			add_post_meta($post_id,'_wpcrm_contact-state',$userState,true);
			add_post_meta($post_id,'_wpcrm_contact-postal',$userZip,true);
			add_post_meta($post_id,'_wpcrm_contact-phone',$userPhone,true);
		// Otherwise, we'll stop
		} else {

    		// Arbitrarily use -2 to indicate that the page with the title already exists
    		$post_id = -2;

		} // end if
		if($post_id) {
			if (-1 == $post_id || -2 == $post_id) { ?>
				<div id="message" class="error">
					<p><strong><?php _e('The contact was not created. A contact with the following name may already exist:', 'wp-crm-system-contact-user') ?> <?php echo $title; ?></strong></p>
				</div>
			<?php } else { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('New contact created:', 'wp-crm-system-contact-user'); ?> <a href="<?php echo get_edit_post_link($post_id); ?>"><?php echo $title; ?></a></strong></p>
				</div>
			<?php }
		}
	}
} // end wpcrm_system_programmatically_create_contact
add_filter( 'after_setup_theme', 'wpcrm_system_programmatically_create_contact' );

function wpcrm_create_contact_action_links($actions, $user_object) {
	$actions['add_contact'] = "<a class='wpcrm_add_contacts' href='" . admin_url( "users.php?action=wpcrm_add_contact_user&amp;user=$user_object->ID") . "'>" . __( 'Add as Contact', 'wp-crm-system-contact-user' ) . "</a>";
	return $actions;
}
add_filter('user_row_actions', 'wpcrm_create_contact_action_links', 10, 2);

// Add license key settings field
function wpcrm_contact_user_license_field() {
	include( plugin_dir_path( __FILE__ ) . 'license.php' );
}
add_action( 'wpcrm_system_license_key_field', 'wpcrm_contact_user_license_field' );


// Add license key status to Dashboard
function wpcrm_system_contact_user_dashboard_license($plugins) {
	// the $plugins parameter is an array of all plugins

	$extra_plugins = array(
		'contact-user'			=> 'wpcrm_contact_from_user_license_status'
	);

	// combine the two arrays
	$plugins = array_merge($extra_plugins, $plugins);

	return $plugins;
}
add_filter('wpcrm_system_dashboard_extensions', 'wpcrm_system_contact_user_dashboard_license');
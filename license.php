<?php
$wpcrm_contact_from_user_key = get_option( 'wpcrm_contact_from_user_license_key' );
$wpcrm_contact_from_user_status = get_option( 'wpcrm_contact_from_user_license_status' );
?>

<tr valign="top">
	<th scope="row" valign="top">
		<?php _e('Contact From User License Key','wp-crm-system-contact-user'); ?>
	</th>
	<td>
		<input id="wpcrm_contact_from_user_license_key" name="wpcrm_contact_from_user_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $wpcrm_contact_from_user_key ); ?>" />
		<label class="description" for="wpcrm_contact_from_user_license_key"><?php _e('Enter your license key','wp-crm-system-contact-user'); ?></label>
	</td>
</tr>
<?php if( false !== $wpcrm_contact_from_user_key ) { ?>
	<tr valign="top">
		<th scope="row" valign="top">
		</th>
		<td>
			<?php if( $wpcrm_contact_from_user_status !== false && $wpcrm_contact_from_user_status == 'valid' ) { ?>
				<span style="color:green;"><?php _e('active'); ?></span>
				<?php wp_nonce_field( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ); ?>
				<input type="submit" class="button-secondary" name="wpcrm_contact_from_user_deactivate" value="<?php _e('Deactivate License','wp-crm-system-contact-user'); ?>"/>
			<?php } else {
				wp_nonce_field( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ); ?>
				<input type="submit" class="button-secondary" name="wpcrm_contact_from_user_activate" value="<?php _e('Activate License','wp-crm-system-contact-user'); ?>"/>
			<?php } ?>
		</td>
	</tr>
<?php } ?>
<?php
/**
* Delete the key used to save options for the plugin.
*/
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();

if (is_admin() && current_user_can('activate_plugins')) {
    include_once(dirname(__FILE__) . '/yfp-login-customizer.php');
    // All of the options are in one location.
    $optKey = Yfp_Login_Customizer::WP_OPTIONS_KEY_NAME;
    delete_option($optKey);
    // For site options in multisite
    delete_site_option( $optKey );
}

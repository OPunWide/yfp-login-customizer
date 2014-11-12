<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();
include_once(dirname(__FILE__) . '/yfp-login-customizer.php');
// All of the options are in one location.
$optKey = Yfp_Login_Customizer::WP_OPTIONS_KEY_NAME;
delete_option($optKey);

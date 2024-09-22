<?php
/*
Plugin Name: Web Accessibility by accessiBe
Plugin URI: https://accessibe.com/
Description: accessiBe is the #1 fully automated web accessibility solution. Protect your website from lawsuits and increase your potential audience.
Version: 1.18
Author: accessiBe
Author URI: https://accessibe.com/
License: GPLv2 or later
Text Domain: accessibe
*/

// this is an include only WP file
if (!defined('ABSPATH')) {
  die;
}

define('ACCESSIBE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACCESSIBE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACCESSIBE_BASENAME', plugin_basename(__FILE__));
define('ACCESSIBE_FILE', __FILE__);
define('ACCESSIBE_OPTIONS_KEY', 'accessibe_options');
define('ACCESSIBE_POINTERS_KEY', 'accessibe_pointers');

require_once(ACCESSIBE_PLUGIN_DIR . 'class.accessibe.php');

register_activation_hook(__FILE__, array('Accessibe', 'activate'));
register_uninstall_hook(__FILE__, array('Accessibe', 'uninstall'));

Accessibe::init();

<?php
/**
  * Plugin Name: Web Accessibility by accessiBe
  * Plugin URI: https://accessibe.com/
  * Description: accessiBe is the #1 fully automated web accessibility solution. Protect your website from lawsuits and increase your potential audience.
  * Version: 2.2
  * Author: accessiBe
  * Author URI: https://accessibe.com/
  * License: GPLv2 or later
  * Text Domain: accessibe
*/

/* this is an include only WP file*/
if (!defined('ABSPATH')) {
  die;
}

require 'vendor/autoload.php';
use Mixpanel\Mixpanel;

define('ACCESSIBE_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACCESSIBE_WP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACCESSIBE_WP_BASENAME', plugin_basename(__FILE__));
define('ACCESSIBE_WP_FILE', __FILE__);
define('ACCESSIBE_WP_OLD_OPTIONS_KEY', 'accessibe_options');
define('ACCESSIBE_WP_OPTIONS_KEY', 'accessibeforwp_options');
define('ACCESSIBE_WP_POINTERS_KEY', 'accessibeforwp_pointers');

require_once(ACCESSIBE_WP_PLUGIN_DIR . 'class.accessibeforwp.php');

register_activation_hook(__FILE__, array('AccessibeWp', 'activate'));
register_uninstall_hook(__FILE__, array('AccessibeWp', 'uninstall'));

AccessibeWp::accessibe_init();

<?php
/**
 * My Plugin old main file upgrade routine
 *
 * This is the old plugin main file.
 * Keep it for backward upgrade compatibility.
 *
 * @package Web Accessibility by accessiBe (older version)
 */

defined('WPINC') || die;

require 'vendor/autoload.php';
use Mixpanel\Mixpanel;

// Hook into WordPress after it has loaded fully
add_action('plugins_loaded', 'handle_plugin_upgrade');

function handle_plugin_upgrade() {
    $old = 'accessiebe.php'; 
    $new = 'accessibe.php';

    $active_plugins = (array) get_option('active_plugins', array());

    $old_plugin_path = basename(__DIR__) . '/' . $old;
    $new_plugin_path = basename(__DIR__) . '/' . $new;

    // Only proceed if the old plugin is active.
    if (in_array($old_plugin_path, $active_plugins)) {
        // Remove the old plugin from the active plugins list.
        $active_plugins = array_diff($active_plugins, array($old_plugin_path));

        // Add the new plugin to the active plugins list if it's not already there.
        if (!in_array($new_plugin_path, $active_plugins)) {
            $active_plugins[] = $new_plugin_path;

            // Include the new plugin file to ensure it is initialized.
            include_once __DIR__ . '/' . $new;
        }

        // Update the active plugins option in the database.
        update_option('active_plugins', $active_plugins);

        track_plugin_upgrade();
    }
}

function track_plugin_upgrade() {
    $uuid = generateUuidV4();
    $current_user = wp_get_current_user();

    if (!$current_user || empty($current_user->ID)) {
        return;
    }

    $mixpanelHandler = new MixpanelHandler();
    $mixpanelHandler->trackEvent(
        'pluginUpgraded',
        [
            '$device_id' => $uuid,
            'pluginVersion' => accessibe_get_plugin_version(),
            'wordpressStoreName' => sanitizeDomain(wp_parse_url(site_url())['host']),
            'wordpressPluginVersionNumber' => accessibe_get_plugin_version(),
            'wordpressAccountUserID' => $current_user->ID,
            'wordpressUserEmail' => $current_user->user_email,
            'wordpressUsername' => $current_user->user_login
        ]
    );

    $current_data = [
        'mixpanelUUID' => $uuid,
        'pluginVersion' => accessibe_get_plugin_version(),
    ];
    update_option('accessibeforwp_options', json_encode($current_data));
}

function generateUuidV4() {
    $data = random_bytes(16);

    // Set version to 4 and variant to 10xx
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function sanitizeDomain($domain) {
    // Use regex to replace "www." only at the beginning
    return preg_replace("/^www\./", "", strtolower($domain));
}

function accessibe_get_plugin_version() {
    $accessibe_plugin_data = get_file_data(ACCESSIBE_WP_FILE, ['version' => 'Version'], 'plugin');
    return $accessibe_plugin_data['version'];
}

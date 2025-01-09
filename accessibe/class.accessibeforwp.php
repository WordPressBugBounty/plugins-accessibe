<?php

require_once(ACCESSIBE_WP_PLUGIN_DIR . 'mixpanel.php');

class AccessibeWp {
    private static $accessibe_initiated = false;
    public static $accessibe_version;
    public static $app_screen_id = "toplevel_page_accessibe";
	public static $DEFAULT_WIDGET_CONFIG = [
        'leadColor' => '#146ff8',
        'language' => 'en',
        'position' => 'left',
        'triggerColor' => '#146ff8',
        'triggerPositionX' => 'right',
        'triggerPositionY' => 'bottom',
        'triggerSize' => 'medium',
        'triggerRadius' => '50%',
        'hideTrigger' => 'false',
        'triggerOffsetX' => '20',
        'triggerOffsetY' => '20',
        'triggerIcon' => 'LegacyPeople',
        'hideMobile' => 'false',
        'mobileTriggerPositionX' => 'right',
        'mobileTriggerPositionY' => 'bottom',
        'mobileTriggerSize' => 'small',
        'mobileTriggerRadius' => '50%',
        'mobileTriggerOffsetX' => '10',
        'mobileTriggerOffsetY' => '10',
        'statementLink' => '',
    ];

    public static $icon_mapping_to_widget = [
            "LegacyDisplay" => "display",
            "LegacyDisplay2" => "display2",
            "LegacyDisplay3" => "display3",
            "LegacyHelp" => "help",
            "LegacyPeople" => "people",
            "LegacyPeople2" => "people2",
            "LegacySettings" => "settings",
            "LegacySettings2" => "settings2",
            "LegacyWheels" => "wheels",
            "LegacyWheels2" => "wheels2",
    ];

    public static function accessibe_init() {
        if (!self::$accessibe_initiated) {
            self::accessibe_init_hooks();
        }
    } // accessibe_init


  /**
   * Initializes WordPress hooks
   */
    private static function accessibe_init_hooks() {
        self::$accessibe_initiated = true;

        /* Options page */
        add_action('admin_menu', array('AccessibeWp', 'accessibe_options_page'));

        /* Register settings */
        // add_action('admin_init', array('AccessibeWp', 'accessibe_register_settings'));

        /* Render js in footer */
        add_action('wp_footer', array('AccessibeWp', 'accessibe_render_js_in_footer'));

        /* Link to settings page */
        add_filter('plugin_action_links_' . ACCESSIBE_WP_BASENAME, array('AccessibeWp', 'accessibe_add_action_links'));

        /* enqueue admin scripts */
        add_action('admin_enqueue_scripts', array('AccessibeWp', 'accessibe_admin_enqueue_scripts'));

        /* dismiss pointer */
        add_action('wp_ajax_accessibe_dismiss_pointer', array('AccessibeWp', 'accessibe_dismiss_pointer_ajax'));

        /* signup */
        add_action('wp_ajax_accessibe_signup', array('AccessibeWp', 'accessibe_signup_ajax'));
        /* login */
        add_action('wp_ajax_accessibe_login', array('AccessibeWp', 'accessibe_login_ajax'));
        /* get merchant details */
        add_action('wp_ajax_accessibe_merchant_detail', array('AccessibeWp', 'accessibe_merchant_detail_ajax'));
        /* get domains list */
        add_action('wp_ajax_accessibe_domain_list', array('AccessibeWp', 'accessibe_domain_list_ajax'));
        /* license trial */
        add_action('wp_ajax_accessibe_license_trial',  array('AccessibeWp', 'accessibe_license_trial_ajax'));
        /* logout */
        add_action('wp_ajax_accessibe_logout',  array('AccessibeWp', 'accessibe_logout'));
        /* inject script */
        add_action('wp_ajax_accessibe_inject_script',  array('AccessibeWp', 'accessibe_inject_script_ajax'));
        /* remove script */
        add_action('wp_ajax_accessibe_remove_script',  array('AccessibeWp', 'accessibe_remove_script_ajax'));
        /* modify config */
        add_action('wp_ajax_accessibe_modify_config',  array('AccessibeWp', 'accessibe_modify_config_ajax'));
        

        /* update admin footer text */
        add_filter('admin_footer_text', array('AccessibeWp', 'accessibe_admin_footer_text'));

    } // accessibe_init_hooks


  /**
   * Get plugin version
   */
    public static function accessibe_get_plugin_version() {
        $accessibe_plugin_data = get_file_data(ACCESSIBE_WP_FILE, array('version' => 'Version'), 'plugin');
        self::$accessibe_version = $accessibe_plugin_data['version'];
        return $accessibe_plugin_data['version'];
    } // accessibe_get_plugin_version

  /**
   * Enqueue Admin Scripts
   */
    public static function accessibe_admin_enqueue_scripts($accessibe_hook) {
        if (self::$app_screen_id == $accessibe_hook) {
            wp_enqueue_style('accessibe-admin', ACCESSIBE_WP_PLUGIN_URL . 'accessibe_inc/css/accessibe.css?v=' . time(), array(), self::accessibe_get_plugin_version());
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_script('accessibe-admin-js', ACCESSIBE_WP_PLUGIN_URL . 'accessibe_inc/js/accessibe.js?v=' . time(), array('jquery'), self::accessibe_get_plugin_version(), true);
        }

        wp_enqueue_script('accessibe-admin-global', ACCESSIBE_WP_PLUGIN_URL . 'accessibe_inc/js/accessibe-global.js', array('jquery'), self::accessibe_get_plugin_version(), true);
        wp_localize_script('accessibe-admin-global', 'accessibe_vars', array('run_tool_nonce' => wp_create_nonce('accessibe_run_tool')));

        $accessibe_pointers = get_option(ACCESSIBE_WP_POINTERS_KEY);
        if ($accessibe_pointers && self::$app_screen_id != $accessibe_hook) {

            $accessibe_pointers['_nonce_dismiss_pointer'] = wp_create_nonce('accessibe_dismiss_pointer');
            wp_enqueue_script('wp-pointer');
            wp_enqueue_style('wp-pointer');
            wp_localize_script('wp-pointer', 'accessibe_pointers', $accessibe_pointers);
        }
    } // accessibe_admin_enqueue_scripts

  /**
   * Add action link
   */
    public static function accessibe_add_action_links($accessibe_links) {
        $accessibe_setting_link = '<a href="' . admin_url('admin.php?page=accessibe') . '">' . __('Settings', 'accessibe') . '</a>';
        array_unshift($accessibe_links, $accessibe_setting_link);

        return $accessibe_links;
    } // accessibe_add_action_links


  /**
   * Render js in footer
   */
    public static function accessibe_render_js_in_footer() {
        $accessibe_options = self::accessibe_get_options();
        $current_domain = wp_parse_url(site_url())['host'];
        // $current_domain = '9cc3-2405-201-5c0f-d070-14fd-b303-b02-1999.ngrok-free.app';
        
        if ((!isset($accessibe_options['accessibe']) && (!isset($accessibe_options['script']) || !isset($accessibe_options['script'][$current_domain]))) 
            || (isset($accessibe_options['accessibe']) && 'enabled' != $accessibe_options['accessibe'] && (!isset($accessibe_options['script']) || !isset($accessibe_options['script'][$current_domain]))) 
            || (isset($accessibe_options["script"][$current_domain]) && $accessibe_options["script"][$current_domain]['widgetStatus'] != true)) {
            return false;
        }

        if(isset($accessibe_options["script"][$current_domain])) {
            $accessibe_options = $accessibe_options["script"][$current_domain]["widgetConfig"];
            foreach ($accessibe_options as $key => $value) {
                if (strpos($key, 'Offset') !== false) {
                    if (is_numeric($value)) {
                        $accessibe_options[$key] = (int) $value;
                    }
                }
            }
            $icon_value = $accessibe_options['triggerIcon'];
            $accessibe_options['triggerIcon'] = self::$icon_mapping_to_widget[$icon_value];
        }

            echo "<script>(function(){var s=document.createElement('script');e = !document.body ? document.querySelector('head'):document.body;s.src='https://acsbapp.com/apps/app/dist/js/app.js';s.defer=true;s.onload=function(){acsbJS.init({
                statementLink     : '" . esc_url($accessibe_options['statementLink']) . "',
                footerHtml        : '" . esc_html($accessibe_options['footerHtml']) . "',
                hideMobile        : " . esc_html($accessibe_options['hideMobile']) . ",
                hideTrigger       : " . esc_html($accessibe_options['hideTrigger']) . ",
                language          : '" . esc_html($accessibe_options['language']) . "',
                position          : '" . esc_html($accessibe_options['position']) . "',
                leadColor         : '" . esc_html($accessibe_options['leadColor']) . "',
                triggerColor      : '" . esc_html($accessibe_options['triggerColor']) . "',
                triggerRadius     : '" . esc_html($accessibe_options['triggerRadius']) . "',
                triggerPositionX  : '" . esc_html($accessibe_options['triggerPositionX']) . "',
                triggerPositionY  : '" . esc_html($accessibe_options['triggerPositionY']) . "',
                triggerIcon       : '" . esc_html($accessibe_options['triggerIcon']) . "',
                triggerSize       : '" . esc_html($accessibe_options['triggerSize']) . "',
                triggerOffsetX    : " . esc_html($accessibe_options['triggerOffsetX']) . ",
                triggerOffsetY    : " . esc_html($accessibe_options['triggerOffsetY']) . ",
                mobile            : {
                    triggerSize       : '" . (isset($accessibe_options['mobileTriggerSize']) ? esc_html($accessibe_options['mobileTriggerSize']) : esc_html($accessibe_options['mobile_triggerSize'])) . "',
                    triggerPositionX  : '" . (isset($accessibe_options['mobileTriggerPositionX']) ? esc_html($accessibe_options['mobileTriggerPositionX']) : esc_html($accessibe_options['mobile_triggerPositionX'])) . "',
                    triggerPositionY  : '" . (isset($accessibe_options['mobileTriggerPositionY']) ? esc_html($accessibe_options['mobileTriggerPositionY']) : esc_html($accessibe_options['mobile_triggerPositionY'])) . "',
                    triggerOffsetX    : " . (isset($accessibe_options['mobileTriggerOffsetX']) ? esc_html($accessibe_options['mobileTriggerOffsetX']) : esc_html($accessibe_options['mobile_triggerOffsetX'])) . ",
                    triggerOffsetY    : " . (isset($accessibe_options['mobileTriggerOffsetY']) ? esc_html($accessibe_options['mobileTriggerOffsetY']) : esc_html($accessibe_options['mobile_triggerOffsetY'])) . ",
                    triggerRadius     : '" . (isset($accessibe_options['mobileTriggerRadius']) ? esc_html($accessibe_options['mobileTriggerRadius']) : esc_html($accessibe_options['mobile_triggerRadius'])) . "'
                }
            });
        };
    e.appendChild(s);}());</script>";
    } // accessibe_render_js_in_footer


  /**
   * Reset accessibe_pointers
   */
    public static function accessibe_reset_pointers() {
        $accessibe_pointers = array();
        $accessibe_pointers['welcome'] = array('target' => '#menu-settings', 'edge' => 'left', 'align' => 'right', 'content' => 'Thank you for installing the <b>Web Accessibility by accessiBe</b> plugin. Please open <a href="' . admin_url('admin.php?page=accessibe') . '">Web Accessibility by accessiBe</a> to configure it.');
        update_option(ACCESSIBE_WP_POINTERS_KEY, $accessibe_pointers);
    } // reset_accessibe_pointers


  /**
   * Dismiss pointer
   */
    public static function accessibe_dismiss_pointer_ajax() {
        delete_option(ACCESSIBE_WP_POINTERS_KEY);
    } // accessibe_dismiss_pointer_ajax


    public static function accessibe_merchant_detail_ajax() {
        $current_user = wp_get_current_user();
        $current_user_options = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY));
        $detail = array(
            'source' => 'WordPress',
            'userId' => $current_user->ID,
            'email' => $current_user->user_email,
            'fullName' => $current_user->display_name,
            'storeId' => wp_parse_url(site_url())['host'],
            // 'storeId' => '9cc3-2405-201-5c0f-d070-14fd-b303-b02-1999.ngrok-free.app'
        );
        
        $active_license_id = $current_user_options->activeLicenseId;
        // Conditionally add fields if isLoggedIn is true
        if (isset($current_user_options->activeLicenseId) && isset($current_user_options->licenses) && $current_user_options->activeLicenseId != '') {
            $detail['licenseId'] = $current_user_options->licenses->$active_license_id->licenseId;
            $detail['widgetStatus'] = $current_user_options->licenses->$active_license_id->widgetStatus;
            $detail['widgetConfig'] = $current_user_options->licenses->$active_license_id->widgetConfig;
        }
        // Convert the array to JSON
        $detail_json = json_encode($detail);
        echo $detail_json;
        wp_die();
    } // accessibe_merchant_detail_ajax

    public static function accessibe_domain_list_ajax() {
        $existing_domains = json_decode(stripslashes($_POST['existingDomains']));

        $current_domain = wp_parse_url(site_url())['host'];
        // $current_domain = '9cc3-2405-201-5c0f-d070-14fd-b303-b02-1999.ngrok-free.app';

        $domains_list = array();

        foreach ($existing_domains as $existing_domain) {
            if ($existing_domain->domain == $current_domain) {
                array_push($domains_list, array(
                    'accountId' => $existing_domain->accountId,
                    'licenseId' => $existing_domain->licenseId,
                    'domain' => $current_domain,
                    'siteId' => $current_domain,
                ));
                break;
            }
        }
        if (empty($domains_list)) {
            array_push($domains_list, array(
                'accountId' => '',
                'licenseId' => '',
                'domain' => $current_domain,
                'siteId' => $current_domain,
            ));
        }
    
        echo json_encode($domains_list);
        wp_die();
    } // accessibe_domain_list_ajax

    public static function accessibe_signup_ajax() {

        $data_decoded =  json_decode(stripslashes($_POST['data']));
        error_log(get_option(ACCESSIBE_WP_OPTIONS_KEY));
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY)) ?: (object) [];
        $current_data->email = $data_decoded->email;
        $current_data->acsbUserId = $data_decoded->userId;
        if(isset($current_data->mixpanelUUID)) {
            $mixpanelHandler = new MixpanelHandler();
            $mixpanelHandler->identifyUser($data_decoded->userId, $current_data->mixpanelUUID);
            unset($current_data->mixpanelUUID);
        }
        $current_data->acsbDefaultAccountId = $data_decoded->accountId;
        update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        echo json_encode(array('message' => 'ok'));
        wp_die();
    }

    public static function accessibe_login_ajax() {

        $data_decoded =  json_decode(stripslashes($_POST['data']));
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY)) ?: (object) [];
        $current_data->email = $data_decoded->email;
        $current_data->acsbUserId = $data_decoded->userId;
        if(isset($current_data->mixpanelUUID)) {
            $mixpanelHandler = new MixpanelHandler();
            $mixpanelHandler->identifyUser($data_decoded->userId, $current_data->mixpanelUUID);
            unset($current_data->mixpanelUUID);
        }
        $current_data->acsbDefaultAccountId = $data_decoded->accountId;
        update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        echo json_encode(array('message' => 'ok'));
        wp_die();
    }

    public static function accessibe_license_trial_ajax() {

        $data_decoded =  json_decode(stripslashes($_POST['data']));

        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY)) ?: (object)[];

        if (!isset($current_data->licenses)) {
            $current_data->licenses = (object)[];
        }

        if (!isset($current_data->script)) {
            $current_data->script = (object)[];
        }

        $current_data->activeLicenseId = $data_decoded->licenseId;
        $license_id_to_check = $data_decoded->licenseId;
        $script_domain = $data_decoded->domain;

        if (!isset($current_data->licenses->$license_id_to_check)) {
            $old_data = get_option(ACCESSIBE_WP_OLD_OPTIONS_KEY, array());
            $modified_config = $data_decoded->widgetConfig ?? json_decode(json_encode(self::$DEFAULT_WIDGET_CONFIG));
			$modified_status = true;
            if(!empty($old_data)) {
				$modified_status = 'enabled' == $old_data['accessibe'];
                $modified_config = self::modify_old_data($old_data);
                delete_option(ACCESSIBE_WP_OLD_OPTIONS_KEY);
            }
            $license_data = (object) [
                "siteId" => $data_decoded->siteId,
                "licenseId" => $data_decoded->licenseId,
                "domain" => $data_decoded->domain,
                "accountId" => $data_decoded->accountId,
                "widgetConfig" => $modified_config,
                "widgetStatus" => $modified_status
            ];
            $script_data = (object) [
                "widgetConfig" => $modified_config,
                "widgetStatus" => $modified_status
            ];
            $current_data->script->$script_domain = $script_data;
            $current_data->licenses->$license_id_to_check = $license_data;
            
        }
        update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        echo json_encode($data_decoded);
        wp_die();
    }

    public static function accessibe_logout() {
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY));
        $current_data->activeLicenseId = '';
        update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        echo json_encode(array('message' => 'ok'));
        wp_die();
    }

    public static function accessibe_inject_script_ajax() {
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY));
        $active_license_id = $current_data->activeLicenseId;
        $current_data->licenses->$active_license_id->widgetStatus = true;
        $active_domain = $current_data->licenses->$active_license_id->domain;
        $current_data->script->$active_domain->widgetStatus = true;
        update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        echo json_encode(array('message' => 'ok'));
        wp_die();
    }

    public static function accessibe_remove_script_ajax() {
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY));
        $active_license_id = $current_data->activeLicenseId;
        $current_data->licenses->$active_license_id->widgetStatus = false;
        $active_domain = $current_data->licenses->$active_license_id->domain;
        $current_data->script->$active_domain->widgetStatus = false;
        update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        echo json_encode(array('message' => 'ok'));
        wp_die();
    }

    public static function accessibe_modify_config_ajax() {
        $widgetConfig = json_decode(stripslashes($_POST['widgetConfig']));
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY));
        $active_license_id = $current_data->activeLicenseId;
        $current_data->licenses->$active_license_id->widgetConfig = $widgetConfig;
        $active_domain = $current_data->licenses->$active_license_id->domain;
        $current_data->script->$active_domain->widgetConfig = $widgetConfig;
        update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        echo json_encode(array('message' => 'ok'));
        wp_die();
    }

  /**
   * Initializes Options Page
   */
    public static function accessibe_options_page() {
        add_menu_page(
            'accessiBe',
            'Web Accessibility by accessiBe',
            'manage_options',
            'accessibe',
            array('AccessibeWp', 'accessibe_build_options_page'),
            plugins_url('accessibe_inc/img/accessibe-logo.png', __FILE__)
        );
    } // accessibe_options_page

    /**
   * Get user details
   */
    public static function get_current_admin_user_info() {
    // Get the current logged-in user info
    $current_user = wp_get_current_user();

    // Check if the user has administrative privileges
        $text = '';
    if (current_user_can('manage_options')) {
            $text = $text.'User ID: ' . $current_user->ID . '<br>';
            $text = $text.'Username: ' . $current_user->user_login . '<br>';
            $text = $text.'Email: ' . $current_user->user_email . '<br>';
            $text = $text.'Display Name: ' . $current_user->display_name . '<br>';
            $text = $text.'Roles: ' . implode(', ', $current_user->roles) . '<br>';
            
    } else {
            $text = $text.'You do not have the permissions to access this information.';
    }
        return $text;
    } // get_current_admin_user_info

  /**
   * Admin footer text
   */
    public static function accessibe_admin_footer_text($accessibe_text) {
        if (!self::accessibe_is_plugin_page()) {
            return $accessibe_text;
        }

        $accessibe_text = '<i class="accessibe-footer"><a href="' . self::accessibe_generate_web_link('admin_footer') . '" title="' . __('Visit the accessiBe page for more info', 'accessibe') . '" target="_blank">accessiBe</a> v' . self::accessibe_get_plugin_version() . '. Please <a target="_blank" href="https://wordpress.org/support/plugin/accessibe/reviews/#new-post" title="Rate the plugin">rate the plugin <span>★★★★★</span></a> to help us spread the word. Thank you from the accessiBe team!</i>';
        return $accessibe_text;
    } // accessibe_admin_footer_text


  /**
   * Test if we're on plugin's page
   *
   * @since 5.0
   *
   * @return null
   */
    public static function accessibe_is_plugin_page() {
        $accessibe_current_screen = get_current_screen();
        if (self::$app_screen_id == $accessibe_current_screen->id) {
            return true;
        } else {
            return false;
        }
    } // accessibe_is_plugin_page


  /**
   * Helper function for generating UTM tagged links
   *
   * @param string  $placement  Optional. UTM content param.
   * @param string  $page       Optional. Page to link to.
   * @param array   $params     Optional. Extra URL params.
   * @param string  $anchor     Optional. URL anchor part.
   *
   * @return string
   */
    public static function accessibe_generate_web_link($accessibe_placement = '', $accessibe_page = '/', $accessibe_params = array(), $accessibe_anchor = '') {
        $accessibe_base_url = 'https://accessibe.com';

        if ('/' != $accessibe_page) {
            $accessibe_page = '/' . trim($accessibe_page, '/');
        }
        if ('//' == $accessibe_page) {
            $accessibe_page = '/';
        }

        $accessibe_parts = array_merge(array('utm_source' => 'accessibe', 'utm_medium' => 'plugin', 'utm_content' => $accessibe_placement, 'utm_campaign' => 'accessibe-v' . self::accessibe_get_plugin_version()), $accessibe_params);

        if (!empty($accessibe_anchor)) {
            $accessibe_anchor = '#' . trim($accessibe_anchor, '#');
        }

        $accessibe_out = $accessibe_base_url . $accessibe_page . '?' . http_build_query($accessibe_parts, '', '&amp;') . $accessibe_anchor;

        return $accessibe_out;
    } // accessibe_generate_web_link


  /**
   * Get plugin options
   */
  public static function accessibe_get_options() {
      // Attempt to retrieve and decode options using ACCESSIBE_OPTIONS_KEY
      $accessibe_options = get_option(ACCESSIBE_WP_OPTIONS_KEY, array());
      if(!empty($accessibe_options)) {
        $accessibe_options = json_decode($accessibe_options, true);
      }

      // If decoding fails or ACCESSIBE_OPTIONS_KEY does not exist, fallback to ACCESSIBE_OLD_OPTIONS_KEY
      if(empty($accessibe_options) || !isset($accessibe_options['script'])) {
          $accessibe_options = get_option(ACCESSIBE_WP_OLD_OPTIONS_KEY, array());
      }

      return $accessibe_options;
  }// accessibe_get_options

  public static function generateUuidV4() {
    $data = random_bytes(16);

    // Set version to 4 and variant to 10xx
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }


  public static function modify_old_data($old_data) {
      unset($old_data['accessibe']);
      unset($old_data['feedbackLink']);
      unset($old_data['js_code']);
      $modified_array = [];
      foreach ($old_data as $key => $value) {
          // Replace '_t' with 'T' in the key
          $newKey = str_replace('_t', 'T', $key);
          $modified_array[$newKey] = $value;
      }
      foreach ($modified_array as $key => $value) {
        if (strpos($key, 'Offset') !== false) {
            $modified_array[$key] = (string) $value;
        }
    }
      $modified_array['triggerIcon'] = array_search($modified_array['triggerIcon'], self::$icon_mapping_to_widget);
      $modified_json_string = json_encode($modified_array);
      $modified_object = json_decode($modified_json_string);
      return $modified_object;
  }

  /**
   * Register all settings
   */
    public static function accessibe_register_settings() {
        register_setting(ACCESSIBE_WP_OPTIONS_KEY, ACCESSIBE_WP_OPTIONS_KEY, array('AccessibeWp', 'accessibe_sanitize_settings'));
    } // accessibe_register_settings


  /**
   * Sanitize settings on save
   */
    public static function accessibe_sanitize_settings($accessibe_options) {
        $accessibe_current_options = self::accessibe_get_options();

        // if (!empty($accessibe_options['js_code'])) {
		// 	$accessibe_options = self::accessibe_parse_js($accessibe_options['js_code']);
		// 	unset($accessibe_options['js_code']);
		// }

		// foreach ($accessibe_options as $accessibe_option => $accessibe_value) {
		// 	if (array_key_exists($accessibe_option, $accessibe_defaults)) {
		// 		$accessibe_value = self::accessibe_validate_field($accessibe_option, $accessibe_value);
		// 		if (false !== $accessibe_value) {
		// 			$accessibe_current_options[$accessibe_option] = $accessibe_value;
		// 		}
		// 	}
		// }

        self::accessibe_clear_3rd_party_cache();

        // return $accessibe_current_options;
    } // accessibe_sanitize_settings


  /**
   * Build Options Page
   */
    public static function accessibe_build_options_page() {
        $accessibe_options = self::accessibe_get_options();

        // auto remove welcome pointer when options are opened
        $accessibe_pointers = get_option(ACCESSIBE_WP_POINTERS_KEY);
        if (isset($accessibe_pointers['welcome'])) {
            unset($accessibe_pointers['welcome']);
            update_option(ACCESSIBE_WP_POINTERS_KEY, $accessibe_pointers);
        }
        ?>
        <div class="wrap">
            <iframe id='accessibe-universal-iframe' src='https://universal.accessibe.com'></iframe>
        </div>
        <!-- /.wrap -->
        <?php
    } // accessibe_build_options_page



  /**
   * Clear 3rd Party Cache
   */
    public static function accessibe_clear_3rd_party_cache() {
        wp_cache_flush();
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        if (function_exists('w3tc_pgcache_flush')) {
            w3tc_pgcache_flush();
        }
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache();
        }
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        if (method_exists('LiteSpeed_Cache_API', 'purge_all')) {
            LiteSpeed_Cache_API::purge_all();
        }
        if (class_exists('Endurance_Page_Cache')) {
            $accessibe_epc = new Endurance_Page_Cache();
            $accessibe_epc->purge_all();
        }
        if (class_exists('SG_CachePress_Supercacher') && method_exists('SG_CachePress_Supercacher', 'purge_cache')) {
            SG_CachePress_Supercacher::purge_cache(true);
        }
        if (class_exists('SiteGround_Optimizer\Supercacher\Supercacher')) {
        SiteGround_Optimizer\Supercacher\Supercacher::purge_cache();
        }
        if (isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache')) {
            $GLOBALS['wp_fastest_cache']->deleteCache(true);
        }
        if (is_callable(array('Swift_Performance_Cache', 'clear_all_cache'))) {
            Swift_Performance_Cache::clear_all_cache();
        }
        if (is_callable(array('Hummingbird\WP_Hummingbird', 'flush_cache'))) {
            Hummingbird\WP_Hummingbird::flush_cache(true, false);
        }
    } // accessibe_clear_3rd_party_cache


    public static function activate() {
        self::accessibe_reset_pointers();
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY), true);
        if(!isset($current_data['isInstalled']) || $current_data['isInstalled'] === false) {
            $uuid = self::generateUuidV4();
            $current_user = wp_get_current_user();
            $mixpanelHandler = new MixpanelHandler();
            $mixpanelHandler->trackEvent('pluginInstalled', ['$device_id' => $uuid, 'primaryDomain' => wp_parse_url(site_url())['host'], 'wp_id' => $current_user->ID, 'wp_email' => $current_user->user_email, 'wp_username' => $current_user->user_login, 'wp_display_name' => $current_user->display_name ]);
            $current_data['isInstalled'] = true;
            $current_data['mixpanelUUID'] = $uuid;
            update_option(ACCESSIBE_WP_OPTIONS_KEY, json_encode($current_data));
        }
    } // activate

    public static function uninstall() {
        $current_data = json_decode(get_option(ACCESSIBE_WP_OPTIONS_KEY), true);
        $current_user = wp_get_current_user();
        $mixpanelHandler = new MixpanelHandler();
        if(isset($current_data['acsbuserId'])) {
            $mixpanelHandler->trackEvent('pluginInstalled', ['userId' => $current_data['acsbuserId'], 'primaryDomain' => wp_parse_url(site_url())['host'], 'wp_id' => $current_user->ID, 'wp_email' => $current_user->user_email, 'wp_username' => $current_user->user_login, 'wp_display_name' => $current_user->display_name ]);
        }
        else {
            $mixpanelHandler->trackEvent('pluginInstalled', ['$device_id' => $current_data['mixpanelUUID'], 'primaryDomain' => wp_parse_url(site_url())['host'], 'wp_id' => $current_user->ID, 'wp_email' => $current_user->user_email, 'wp_username' => $current_user->user_login, 'wp_display_name' => $current_user->display_name ]);
        }
        $mixpanelHandler->trackEvent('pluginUninstalled', ['primaryDomain' => wp_parse_url(site_url())['host']]);
        delete_option(ACCESSIBE_WP_OPTIONS_KEY);
        delete_option(ACCESSIBE_WP_POINTERS_KEY);
    } // uninstall
} // class

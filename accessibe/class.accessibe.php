<?php
class Accessibe
{
  private static $initiated = false;
  public static $version;


  public static function init()
  {
    if (!self::$initiated) {
      self::init_hooks();
    }
  } // init


  /**
   * Initializes WordPress hooks
   */
  private static function init_hooks()
  {
    self::$initiated = true;

    /* Options page */
    add_action('admin_menu', array('Accessibe', 'options_page'));

    /* Register settings */
    add_action('admin_init', array('Accessibe', 'register_settings'));

    /* Render js in footer */
    add_action('wp_footer', array('Accessibe', 'render_js_in_footer'));

    /* Link to settings page */
    add_filter('plugin_action_links_' . ACCESSIBE_BASENAME, array('Accessibe', 'add_action_links'));

    /* enqueue admin scripts */
    add_action('admin_enqueue_scripts', array('Accessibe', 'admin_enqueue_scripts'));

    /* dismiss pointer */
    add_action('wp_ajax_accessibe_dismiss_pointer', array('Accessibe', 'dismiss_pointer_ajax'));

    /* update admin footer text */
    add_filter('admin_footer_text', array('Accessibe', 'admin_footer_text'));

    /* import old settings if they exist */
    add_action('init', array('Accessibe', 'import_old_settings'));
  } // init_hooks


  /**
   * Get plugin version
   */
  public static function get_plugin_version()
  {
    $plugin_data = get_file_data(ACCESSIBE_FILE, array('version' => 'Version'), 'plugin');
    self::$version = $plugin_data['version'];

    return $plugin_data['version'];
  } // get_plugin_version


  /**
   * Import old settings if key exists
   */
  public static function import_old_settings()
  {
    $options_old = get_option('accessibe_js');
    if (false !== $options_old) {
      $defaults = self::default_options();
      if (!empty($options_old)) {
        $options_old = self::parse_js($options_old);
      }

      foreach ($options_old as $option_old => $value) {
        if (array_key_exists($option_old, $defaults)) {
          $options[$option_old] = $value;
        }
      }

      update_option(ACCESSIBE_OPTIONS_KEY, $options);
      delete_option('accessibe_js');
    }
  } // import_old_settings


  /**
   * Enqueue Admin Scripts
   */
  public static function admin_enqueue_scripts($hook)
  {
    if ('settings_page_accessiBe' == $hook) {
      wp_enqueue_style('accessibe-admin', ACCESSIBE_PLUGIN_URL . 'inc/css/accessibe.css', array(), self::get_plugin_version());
      wp_enqueue_style('wp-color-picker');
      wp_enqueue_script('wp-color-picker');
      wp_enqueue_script('accessibe-admin', ACCESSIBE_PLUGIN_URL . 'inc/js/accessibe.js', array('jquery'), self::get_plugin_version(), true);
    }

    wp_enqueue_script('accessibe-admin-global', ACCESSIBE_PLUGIN_URL . 'inc/js/accessibe-global.js', array('jquery'), self::get_plugin_version(), true);
    wp_localize_script('accessibe-admin-global', 'accessibe_vars', array('run_tool_nonce' => wp_create_nonce('accessibe_run_tool')));

    $pointers = get_option(ACCESSIBE_POINTERS_KEY);
    if ($pointers && 'settings_page_accessiBe' != $hook) {

      $pointers['_nonce_dismiss_pointer'] = wp_create_nonce('accessibe_dismiss_pointer');
      wp_enqueue_script('wp-pointer');
      wp_enqueue_style('wp-pointer');
      wp_localize_script('wp-pointer', 'accessibe_pointers', $pointers);
    }
  } // admin_enqueue_scripts

  /**
   * Add action link
   */
  public static function add_action_links($links)
  {
    $setting_link = '<a href="' . admin_url('options-general.php?page=accessiBe') . '">' . __('Settings', 'accessibe') . '</a>';
    array_unshift($links, $setting_link);

    return $links;
  } // add_action_links


  /**
   * Render js in footer
   */
  public static function render_js_in_footer()
  {
    $options = self::get_options();
    if ($options['accessibe'] != 'enabled') {
      return false;
    }

    echo "<script>(function(){var s=document.createElement('script');e = !document.body ? document.querySelector('head'):document.body;s.src='https://acsbapp.com/apps/app/dist/js/app.js';s.defer=true;s.onload=function(){acsbJS.init({
            statementLink     : '" . esc_url($options['statementLink']) . "',
            feedbackLink      : '" . esc_url($options['feedbackLink']) . "',
            footerHtml        : '" . esc_html($options['footerHtml']) . "',
            hideMobile        : "  . esc_html($options['hideMobile']) . ",
            hideTrigger       : "  . esc_html($options['hideTrigger']) . ",
            language          : '" . esc_html($options['language']) . "',
            position          : '" . esc_html($options['position']) . "',
            leadColor         : '" . esc_html($options['leadColor']) . "',
            triggerColor      : '" . esc_html($options['triggerColor']) . "',
            triggerRadius     : '" . esc_html($options['triggerRadius']) . "',
            triggerPositionX  : '" . esc_html($options['triggerPositionX']) . "',
            triggerPositionY  : '" . esc_html($options['triggerPositionY']) . "',
            triggerIcon       : '" . esc_html($options['triggerIcon']) . "',
            triggerSize       : '" . esc_html($options['triggerSize']) . "',
            triggerOffsetX    : "  . esc_html($options['triggerOffsetX']) . ",
            triggerOffsetY    : "  . esc_html($options['triggerOffsetY']) . ",
            mobile            : {
                triggerSize       : '" . esc_html($options['mobile_triggerSize']) . "',
                triggerPositionX  : '" . esc_html($options['mobile_triggerPositionX']) . "',
                triggerPositionY  : '" . esc_html($options['mobile_triggerPositionY']) . "',
                triggerOffsetX    : "  . esc_html($options['mobile_triggerOffsetX']) . ",
                triggerOffsetY    : "  . esc_html($options['mobile_triggerOffsetY']) . ",
                triggerRadius     : '" . esc_html($options['mobile_triggerRadius']) . "'
            }
        });
    };
    e.appendChild(s);}());</script>";
  } // render_js_in_footer


  /**
   * Reset pointers
   */
  public static function reset_pointers()
  {
    $pointers = array();
    $pointers['welcome'] = array('target' => '#menu-settings', 'edge' => 'left', 'align' => 'right', 'content' => 'Thank you for installing the <b>Web Accessibility by accessiBe</b> plugin. Please open <a href="' . admin_url('options-general.php?page=accessiBe') . '">Settings - accessiBe</a> to configure it.');

    update_option(ACCESSIBE_POINTERS_KEY, $pointers);
  } // reset_pointers


  /**
   * Dismiss pointer
   */
  public static function dismiss_pointer_ajax()
  {
    delete_option(ACCESSIBE_POINTERS_KEY);
  } // dismiss_pointer_ajax


  /**
   * Initializes Options Page
   */
  public static function options_page()
  {
    add_options_page(
      'accessiBe',
      'accessiBe',
      'manage_options',
      'accessiBe',
      array(
        'Accessibe',
        'build_options_page',
      )
    );
  } // options_page


  /**
   * Admin footer text
   */
  public static function admin_footer_text($text)
  {
    if (!self::is_plugin_page()) {
      return $text;
    }

    $text = '<i class="accessibe-footer"><a href="' . self::generate_web_link('admin_footer') . '" title="' . __('Visit the accessiBe page for more info', 'accessibe') . '" target="_blank">accessiBe</a> v' . self::get_plugin_version() . '. Please <a target="_blank" href="https://wordpress.org/support/plugin/accessibe/reviews/#new-post" title="Rate the plugin">rate the plugin <span>★★★★★</span></a> to help us spread the word. Thank you from the accessiBe team!</i>';

    return $text;
  } // admin_footer_text


  /**
   * Test if we're on plugin's page
   *
   * @since 5.0
   *
   * @return null
   */
  public static function is_plugin_page()
  {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'settings_page_accessiBe') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page


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
  public static function generate_web_link($placement = '', $page = '/', $params = array(), $anchor = '')
  {
    $base_url = 'https://accessibe.com';

    if ('/' != $page) {
      $page = '/' . trim($page, '/');
    }
    if ($page == '//') {
      $page = '/';
    }

    $parts = array_merge(array('utm_source' => 'accessibe', 'utm_medium' => 'plugin', 'utm_content' => $placement, 'utm_campaign' => 'accessibe-v' . self::get_plugin_version()), $params);

    if (!empty($anchor)) {
      $anchor = '#' . trim($anchor, '#');
    }

    $out = $base_url . $page . '?' . http_build_query($parts, '', '&amp;') . $anchor;

    return $out;
  } // generate_web_link


  /**
   * Get plugin options
   */
  public static function get_options()
  {
    $options = get_option(ACCESSIBE_OPTIONS_KEY, array());

    if (!is_array($options)) {
      $options = array();
    }
    $options = array_merge(self::default_options(), $options);

    return $options;
  } // get_options


  /**
   * Register all settings
   */
  public static function register_settings()
  {
    register_setting(ACCESSIBE_OPTIONS_KEY, ACCESSIBE_OPTIONS_KEY, array('Accessibe', 'sanitize_settings'));
  } // register_settings


  /**
   * Sanitize settings on save
   */
  public static function sanitize_settings($options)
  {
    $current_options = self::get_options();
    $defaults = self::default_options();

    if (!empty($options['js_code'])) {
      $options = self::parse_js($options['js_code']);
      unset($options['js_code']);
    }

    foreach ($options as $option => $value) {
      if (array_key_exists($option, $defaults)) {
        $value = self::validate_field($option, $value);
        if (false !== $value) {
          $current_options[$option] = $value;
        }
      }
    }

    self::clear_3rd_party_cache();

    return $current_options;
  } // sanitize_settings


  /**
   * Validate field value
   */
  public static function validate_field($field_name, $value)
  {
    $fields = self::get_fields_list();
    $fields_array = array();
    foreach ($fields as $field) {
      $fields_array[$field['name']] = $field;
    }

    if (!array_key_exists($field_name, $fields_array)) {
      return true;
    }

    switch ($fields_array[$field_name]['type']) {
      case 'textarea':
        return $value;
        break;
      case 'text':
      case 'color':
      case 'radio':
      case 'checkbox':
        return strip_tags($value);
        break;
      case 'number':
        return (int) $value;
        break;
      case 'select':
        if (array_key_exists($value, $fields_array[$field_name]['options'])) {
          return $value;
        } else {
          return false;
        }
        break;
    }
  } // validate_field

  /**
   * Set default options
   */
  public static function default_options()
  {
    $defaults = array();
    $fields = self::get_fields_list();
    foreach ($fields as $field) {
      $defaults[$field['name']] = $field['default'];
    }

    return $defaults;
  } // default_options


  /**
   * Build Options Page
   */
  public static function build_options_page()
  {
    $options = self::get_options();

    // auto remove welcome pointer when options are opened
    $pointers = get_option(ACCESSIBE_POINTERS_KEY);
    if (isset($pointers['welcome'])) {
      unset($pointers['welcome']);
      update_option(ACCESSIBE_POINTERS_KEY, $pointers);
    }
?>
    <div class="wrap">
      <h2><img class="accessibe-logo" src="<?php echo esc_url(ACCESSIBE_PLUGIN_URL . 'inc/img/accessiBe-logo.svg'); ?>" alt="accessiBe" title="accessiBe"></h2>

      <form method="POST" action="options.php">
        <?php
        $fields = self::get_fields_list();
        foreach ($fields as $field) {
          echo self::get_field_html($field, $options[$field['name']]);
        }
        settings_fields(ACCESSIBE_OPTIONS_KEY);
        submit_button(false, 'primary', 'submit', false);
        echo '<a class="accessibe-support button" href="' . esc_url(self::generate_web_link('need-help', 'support')) . '" target="_blank"><span class="dashicons dashicons-sos"></span> Need help?</a>';
        ?>
      </form>

    </div>
    <!-- /.wrap -->
<?php
  } // build_options_page


  /**
   * Get field HTML
   */
  public static function get_field_html($field, $value)
  {
    $out = '<div class="accessibe-field ' . (isset($field['extra']) && strpos($field['extra'], 'fullwidth') !== false ? 'accessibe-field-full' : '') . '">';
    $out .= '<label for="' . $field['name'] . '">' . $field['label'] . '</label>';
    switch ($field['type']) {
      case 'text':
        $out .= '<input type="text" name="' . ACCESSIBE_OPTIONS_KEY . '[' . $field['name'] . ']" id="' . $field['name'] . '" value="' . $value . '" />';
        break;
      case 'select':
        $out .= '<select name="' . ACCESSIBE_OPTIONS_KEY . '[' . $field['name'] . ']" id="' . $field['name'] . '">';
        foreach ($field['options'] as $option => $text) {
          $out .= '<option value="' . $option . '" ' . ($option === $value ? 'selected' : '') . '>' . $text . '</option>';
        }
        $out .= '</select>';
        break;
      case 'number':
        $out .= '<input type="number" name="' . ACCESSIBE_OPTIONS_KEY . '[' . $field['name'] . ']" id="' . $field['name'] . '" value="' . $value . '" style="width:80px;" />';
        break;
      case 'color':
        $out .= '<input class="accessibe-color-field" type="text" name="' . ACCESSIBE_OPTIONS_KEY . '[' . $field['name'] . ']" id="' . $field['name'] . '" value="' . $value . '" style="width:80px;" />';
        break;
      case 'radio':
        foreach ($field['options'] as $option => $text) {
          if (strpos($field['extra'], 'img') !== false) {
            $out .= '<div class="accessibe-icon-wrapper">';
          }

          if (strpos($field['extra'], 'img') !== false) {
            $out .= '<label for="' . $field['name'] . '_' . $option . '"><img src="' . ACCESSIBE_PLUGIN_URL . '/inc/img/icons/' . $text . '.svg" /></label>';
          } else {
            $out .= $text;
          }

          $out .= '<input type="radio" name="' . ACCESSIBE_OPTIONS_KEY . '[' . $field['name'] . ']" id="' . $field['name'] . '_' . $option . '" value="' . $text . '" ' . ($value == $text ? 'checked' : '') . ' />';

          if (strpos($field['extra'], 'img') !== false) {
            $out .= '</div>';
          }
        }
        break;
      case 'checkbox':
        $out .= '<input type="checkbox" name="' . ACCESSIBE_OPTIONS_KEY . '[' . $field['name'] . ']" id="' . $field['name'] . '" value="true" ' . ($value == 'true' ? 'checked' : '') . ' />';
        break;
      case 'textarea':
        $out .= '<textarea name="' . ACCESSIBE_OPTIONS_KEY . '[' . $field['name'] . ']" id="' . $field['name'] . '">' . $value . '</textarea>';
        break;
    }

    if (isset($field['desc'])) {
      $out .= '<p>' . $field['desc'] . '</p>';
    }

    $out .= '</div>';

    return $out;
  } // get_field_html


  /**
   * Get field/optios list
   */
  public static function get_fields_list()
  {
    return [
      [
        'name'    => 'accessibe',
        'label'   => 'accessiBe Status',
        'type'    => 'select',
        'default' => 'enabled',
        'extra'   => 'fullwidth',
        'options' => [
          'enabled'  => 'Enabled',
          'disabled' => 'Disabled'
        ],
        'desc'    => 'Please make sure you <a href="' . self::generate_web_link('create_account') . '" target="_blank">create an account on  accessibe.com</a> and register your site\'s domain. Otherwise the plugin will not work.'
      ],
      [
        'name'    => 'statementLink',
        'label'   => 'Accessibility Statement Link',
        'type'    => 'text',
        'default' => ''
      ],
      [
        'name'    => 'footerHtml',
        'label'   => 'Interface Footer Content',
        'type'    => 'text',
        'default' => ''
      ],
      [
        'name'    => 'language',
        'label'   => 'Interface Language',
        'type'    => 'select',
        'default' => 'en',
        'options' => [
          'en'  => 'English',
          'es'  => 'Español',
          'fr'  => 'Français',
          'de'  => 'Deutsche',
          'it'  => 'Italiano',
          'pt'  => 'Português',
          'nl'  => 'Nederlands',
          'jp'  => '日本語',
          'tw'  => '台灣',
          'ct'  => '中文',
          'he'  => 'עברית',
          'ru'  => 'русский',
          'ar'  => 'الإمارات العربية المتحدة',
          'ar1' => 'عربى',
        ]
      ],
      [
        'name'    => 'leadColor',
        'label'   => 'Interface Lead Color',
        'type'    => 'color',
        'default' => '#146FF8'
      ],
      [
        'name'    => 'triggerColor',
        'label'   => 'Trigger Button Color',
        'type'    => 'color',
        'default' => '#146FF8'
      ],
      [
        'name'    => 'position',
        'label'   => 'Interface Position',
        'type'    => 'select',
        'default' => 'left',
        'options' => [
          'left'  => 'Left',
          'right' => 'Right'
        ]
      ],
      [
        'name'    => 'hideMobile',
        'label'   => 'Show On Mobile?',
        'type'    => 'select',
        'default' => 'false',
        'options' => [
          'true'  => 'Hide',
          'false' => 'Show'
        ]
      ],
      [
        'name'    => 'triggerPositionX',
        'label'   => 'Trigger Horizontal Position',
        'type'    => 'select',
        'default' => 'left',
        'options' => [
          'left'  => 'Left',
          'right' => 'Right'
        ]
      ],
      [
        'name'    => 'triggerPositionY',
        'label'   => 'Trigger Vertical Position',
        'type'    => 'select',
        'default' => 'bottom',
        'options' => [
          'top'    => 'Top',
          'center' => 'Center',
          'bottom' => 'Bottom'
        ]
      ],
      [
        'name'    => 'mobile_triggerPositionX',
        'label'   => 'Mobile Trigger Horizontal Position',
        'type'    => 'select',
        'default' => 'left',
        'options' => [
          'left'  => 'Left',
          'right' => 'Right'
        ]
      ],
      [
        'name'    => 'mobile_triggerPositionY',
        'label'   => 'Mobile Trigger Vertical Position',
        'type'    => 'select',
        'default' => 'center',
        'options' => [
          'top'    => 'Top',
          'center' => 'Center',
          'bottom' => 'Bottom'
        ]
      ],
      [
        'name'    => 'triggerSize',
        'label'   => 'Trigger Button Size',
        'type'    => 'select',
        'default' => 'medium',
        'options' => [
          'small'  => 'Small',
          'medium' => 'Medium',
          'big'    => 'Big'
        ]
      ],
      [
        'name'    => 'mobile_triggerSize',
        'label'   => 'Mobile Trigger Size',
        'type'    => 'select',
        'default' => 'small',
        'options' => [
          'small'  => 'Small',
          'medium' => 'Medium',
          'big'    => 'Big'
        ]
      ],
      [
        'name'    => 'triggerRadius',
        'label'   => 'Trigger Button Shape',
        'type'    => 'select',
        'default' => '50%',
        'options' => [
          '50%'  => 'Round',
          '0'    => 'Square',
          '10px' => 'Squircle Big',
          '5px'  => 'Squircle Small'
        ]
      ],
      [
        'name'    => 'mobile_triggerRadius',
        'label'   => 'Trigger Mobile Shape',
        'type'    => 'select',
        'default' => '0',
        'options' => [
          '50%'  => 'Round',
          '0'    => 'Square',
          '10px' => 'Squircle Big',
          '5px'  => 'Squircle Small'
        ]
      ],
      [
        'name'    => 'triggerIcon',
        'label'   => '',
        'type'    => 'radio',
        'extra'   => 'fullwidth img',
        'options' => [
          'display',
          'display2',
          'display3',
          'help',
          'people',
          'people2',
          'settings',
          'settings2',
          'wheels',
          'wheels2'
        ],
        'default' => 'people'
      ],
      [
        'name'    => 'triggerOffsetX',
        'label'   => 'Trigger Horizontal Offset',
        'type'    => 'number',
        'default' => '20'
      ],
      [
        'name'    => 'triggerOffsetY',
        'label'   => 'Trigger Vertical Offset',
        'type'    => 'number',
        'default' => '20'
      ],
      [
        'name'    => 'mobile_triggerOffsetX',
        'label'   => 'Mobile Trigger Horizontal Offset',
        'type'    => 'number',
        'default' => '0'
      ],
      [
        'name'    => 'mobile_triggerOffsetY',
        'label'   => 'Mobile Trigger Vertical Offset',
        'type'    => 'number',
        'default' => '0'
      ],
      [
        'name'    => 'feedbackLink',
        'label'   => 'Feedback Form Link',
        'type'    => 'text',
        'default' => ''
      ],
      [
        'name'    => 'hideTrigger',
        'label'   => 'Hide Trigger Button',
        'type'    => 'select',
        'default' => 'false',
        'options' => [
          'true'  => 'Hide',
          'false' => 'Show'
        ]
      ],
      [
        'name'    => 'js_code',
        'label'   => 'Custom Installation Script (JavasScript Code):',
        'type'    => 'textarea',
        'default' => '',
        'extra'   => 'fullwidth',
        'desc'    => 'If you have a customized installation script (generated by accessibe.com) that you would like to import, paste it above and it will overwrite your current settings when you click Save Changes'
      ],

    ];
  } // get_fields_list


  /**
   * Clear 3rd Party Cache
   */
  public static function clear_3rd_party_cache()
  {
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
      $epc = new Endurance_Page_Cache;
      $epc->purge_all();
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
  } // clear_3rd_party_cache


  /**
   * Parse JS Code
   */
  public static function parse_js($js)
  {
    $js_values = array();
    $js_part_a = explode('acsbJS.init({', $js, 2);
    $js_part_b = explode('});', $js_part_a[1]);
    $js_part_c = explode('mobile : {', $js_part_b[0]);
    $js_values_mobile_raw = explode(',', rtrim($js_part_c[1], '}'));
    $js_values_raw = explode(',', $js_part_c[0]);
    foreach ($js_values_raw as $value) {
      if (empty(trim($value))) {
        continue;
      }
      $value = explode(' : ', $value);
      $js_values[trim($value[0])] = ltrim(rtrim($value[1], "'"), "'");
    }

    foreach ($js_values_mobile_raw as $value) {
      if (empty(trim($value))) {
        continue;
      }
      $value = explode(' : ', $value);
      $js_values['mobile_' . trim($value[0])] = ltrim(rtrim($value[1], "'"), "'");
    }

    return $js_values;
  } // parse_js


  public static function activate()
  {
    self::reset_pointers();
  } // activate


  public static function uninstall()
  {
    delete_option(ACCESSIBE_OPTIONS_KEY);
    delete_option(ACCESSIBE_POINTERS_KEY);
  } // uninstall
} // class

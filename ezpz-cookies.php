<?php

/**
 * Plugin Name:  EZPZ Cookie Bar
 * Plugin URI:   https://github.com/epls-design/ezpz-cookies/
 * Description:  Simple GDPR compliant cookie bar for Wordpress. Allows the website administrator to add analytics code to the header, footer and body via the dashboard. These tracking codes will only run on user acceptance of the cookie policy.
 * Version:      1.2.0
 * Author:       EPLS Design
 * Author URI:   https://epls.design
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:  ezpz-cookies
 */

// Some help from https://wordpress.org/plugins/caching-compatible-cookie-optin-and-javascript/

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('EzpzCookies')) {

  class EzpzCookies
  {

    private $cookie_scripts = null;
    private $cookie_settings = null;
    private $cookie_name = null;
    private $plugin_slug = null;
    private $plugin_version = null;

    /**
     * __construct() Sets up the class functionality
     */
    function __construct()
    {
      // Set up name for Cookie Bar based on Site URL
      $this->plugin_slug = get_bloginfo('name') && get_bloginfo('name') != ''  ? sanitize_title(get_bloginfo('name')) : 'epls';
      $this->cookie_name = $this->camel_case($this->plugin_slug) . 'CookiePrefs';
      $this->plugin_version = "1.2.0";

      // Retrieve Opts
      $this->set_options();

      // Back End
      $this->admin_init();

      // Front End
      $this->frontend_init();
    }

    /**
     * Helpers
     */

    public function camel_case($str, array $noStrip = [])
    {
      // non-alpha and non-numeric characters become spaces
      $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
      $str = trim($str);
      // uppercase the first character of each word
      $str = ucwords(strtolower($str));
      $str = str_replace(" ", "", $str);
      $str = lcfirst($str);

      return $str;
    }

    /**
     * Setters and Getters
     */
    private function set_options()
    {
      $this->cookie_scripts = get_option('ezpz_cookiebar_scripts');
      $this->cookie_settings = get_option('ezpz_cookiebar_settings');
    }

    public function get_options($type = 'scripts')
    {

      if ($type == 'scripts') :
        return empty($this->cookie_scripts) ? array() : apply_filters('ezpz_scripts', $this->cookie_scripts);

      elseif ($type == 'settings') :
        $settings = empty($this->cookie_settings) ? array() : $this->cookie_settings;

        // Set defaults
        if (!isset($settings['text']['cookie_bar_heading']) || empty($settings['text']['cookie_bar_heading'])) $settings['text']['cookie_bar_heading'] = __('This website uses cookies', 'ezpz-cookies');
        if (!isset($settings['text']['cookie_bar_message']) || empty($settings['text']['cookie_bar_message'])) {
          $settings['text']['cookie_bar_message'] = sprintf(
            '%s<a href="/cookies/" tabindex="3">%s</a>%s',
            __('We use marketing and analytics cookies to help us better understand how visitors use our website and to improve the user experience. You can switch these cookies off if you would like. Read more about how we use cookies on our '),
            __('cookie policy'),
            __(' page.'),
          );
        }
        if (!isset($settings['style']) || empty($settings['style'])) $settings['style'] = 'intrusive';

        return apply_filters('ezpz_settings', $settings);

      else :
        return null;
      endif;
    }

    /**
     * Init Admin Dashboard
     */
    private function admin_init()
    {
      add_action('admin_menu', array($this, 'create_admin_page'));
      add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Initialize Cookiebar on the front end
     */
    private function frontend_init()
    {
      add_action('init', array($this, 'register_shortcodes'));
      add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
      add_action('wp_head', array($this, 'render_essential_header_scripts'), 100);
      add_action('wp_body_open', array($this, 'render_essential_body_scripts'));
    }

    /**
     * Register Shortcodes
     */
    function register_shortcodes()
    {
      add_shortcode('ezpz-cookiebar', array($this, 'ezpz_cookiebar_shortcode'));
    }
    function ezpz_cookiebar_shortcode()
    {
      $html = '<a href="javascript:void(0);" class="cookiebar-show">' . __('Adjust cookie settings', 'ezpz-cookies') . '</a>';
      return $html;
    }

    /**
     * Enqueue CSS and JS on Front End
     */
    function enqueue_scripts()
    {

      wp_enqueue_style($this->plugin_slug . '-cookies', plugins_url('cookie-bar.css', __FILE__), '', $this->plugin_version);

      wp_register_script($this->plugin_slug . '-cookies', plugins_url('cookie-bar.js', __FILE__), array('jquery'), $this->plugin_version, true);
      $js_varname = $this->cookie_name;
      $js_settings = array(
        'scripts' => $this->get_options(),
        'settings' => $this->get_options('settings'),
      );

      if ($this->get_options('settings')['cookies']['policy_page'] != 0 && is_page($this->get_options('settings')['cookies']['policy_page'])) {
        $js_settings['settings']['hide_banner'] = 'true';
      }

      wp_localize_script($this->plugin_slug . '-cookies', 'ezpzCookieName', $js_varname);
      wp_localize_script($this->plugin_slug . '-cookies', 'ezpzCookieSettings', $js_settings);
      wp_enqueue_script($this->plugin_slug . '-cookies');
    }

    /**
     * Register Setting Groups
     */
    function register_settings()
    {
      register_setting(
        'ezpz_cookiebar_scripts_group',  // option_group
        'ezpz_cookiebar_scripts', // option_name
      );
      register_setting(
        'ezpz_cookiebar_settings_group',  // option_group
        'ezpz_cookiebar_settings', // option_name
      );
    }

    /**
     * Add Admin Menu Item
     */
    function create_admin_page()
    {
      add_options_page(
        __('Cookie Settings'), // Page Title
        __('Cookie Settings'), // Menu Title
        'manage_options', // Capability
        plugin_basename(__FILE__), // Menu Slug
        array($this, 'render_admin_page'), // Function
      );
      $this->register_settings();
    }

    /**
     * Render Admin Settings Page
     */
    public function render_admin_page()
    {
      // check user capabilities
      if (!current_user_can('manage_options')) {
        return;
      }

      //Get the active tab from the $_GET param
      $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'scripts';

      $tabs = [
        __('Scripts', 'ezpz-cookies')  => 'scripts',
        __('Settings', 'ezpz-cookies') => 'settings'
      ];

?>
      <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <!-- TODO: Add if updated message here -->

        <h2 class="nav-tab-wrapper">
          <?php foreach ($tabs as $tab_name => $tab_slug) :
            $tab_class = 'nav-tab';
            if ($active_tab == $tab_slug) $tab_class .= ' nav-tab-active';
            echo '<a href="?page=' . plugin_basename(__FILE__) . '&amp;tab=' . $tab_slug . '" class="' . $tab_class . '">' . $tab_name . '</a>';
          endforeach; ?>
        </h2>
        <div class="tab-content">
          <form method="post" action="options.php">
            <?php switch ($active_tab):
              case 'settings':
                $this->create_settings_options();
                settings_fields('ezpz_cookiebar_settings_group');
                do_settings_sections(plugin_basename(__FILE__));
                break 1;
              default:
                $this->create_scripts_options();
                settings_fields('ezpz_cookiebar_scripts_group');
                do_settings_sections(plugin_basename(__FILE__));
                break 1;
            endswitch;

            // Dynamic Submit Button
            submit_button(sprintf(esc_html__('Save %s', 'ezpz-cookies'), ucfirst($active_tab)));
            ?>
          </form>
        </div>
      </div>
<?php
    }


    /**
     * Enqueue Admin CSS
     */
    function enqueue_admin_scripts($hook)
    {
      if ($hook != 'settings_page_ezpz-cookies/ezpz-cookies') return;
      wp_enqueue_style('cookiebar_admin_css', plugins_url('cookie-bar-admin.css', __FILE__));
    }





    public function callback_essential_section_title()
    {
      _e('The scripts you add here will always be executed; they are considered to be essential scripts and not opt-in/opt-out. Do not add marketing or tracking scripts here.');
    }

    public function callback_optin_section_title()
    {
      _e('The scripts you add here will only be executed if the user explicitly opts-in to tracking/analytics cookies.');
    }

    /**
     * Initializes options for the Settings tab
     */
    public function create_settings_options()
    {
      add_settings_section(
        'ezpz_cookiebar_settings', // id
        __('Settings', 'ezpz-cookies'), // title
        null, // callback
        plugin_basename(__FILE__) // page
      );

      add_settings_field(
        'cookie_bar_heading', // id
        __('Cookie Bar Heading', 'ezpz-cookies'), // title
        array($this, 'callback_cookiebar_heading'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_settings' // section
      );

      add_settings_field(
        'cookie_bar_message', // id
        __('Cookie Bar Message', 'ezpz-cookies'), // title
        array($this, 'callback_cookiebar_message'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_settings' // section
      );

      add_settings_field(
        'cookie_policy_page', // id
        esc_html__('Cookie Policy Page', 'ezpz-cookies'), // title
        array($this, 'callback_cookie_policy_page'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_settings' // section
      );

      add_settings_field(
        'cookie_bar_delete_cookies', // id
        __('Cookies to revoke when opting out', 'ezpz-cookies'), // title
        array($this, 'callback_cookiebar_revoke'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_settings' // section
      );

      add_settings_field(
        'cookie_bar_style', // id
        __('Cookie Bar Style', 'ezpz-cookies'), // title
        array($this, 'callback_cookiebar_style'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_settings' // section
      );

      add_settings_field(
        'cookie_bar_active', // id
        __('Cookie Bar Active?', 'ezpz-cookies'), // title
        array($this, 'callback_cookiebar_toggle'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_settings' // section
      );
    }

    /**
     * Initializes options for the Scripts tab
     */
    public function create_scripts_options()
    {

      add_settings_section(
        'ezpz_cookiebar_essential_scripts', // id
        __('Essential Scripts', 'ezpz-cookies'), // title
        array($this, 'callback_essential_section_title'), // callback
        plugin_basename(__FILE__) // page
      );

      add_settings_field(
        'essential_header_scripts', // id
        esc_html('Essential scripts executed in <head>:', 'ezpz-cookies'), // title
        array($this, 'callback_essential_head_scripts'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_essential_scripts' // section
      );

      add_settings_field(
        'essential_body_scripts', // id
        esc_html('Essential scripts executed in <body>:', 'ezpz-cookies'), // title
        array($this, 'callback_essential_body_scripts'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_essential_scripts' // section
      );

      add_settings_section(
        'ezpz_cookiebar_optin_scripts', // id
        __('Opt-In Scripts', 'ezpz-cookies'), // title
        array($this, 'callback_optin_section_title'), // callback
        plugin_basename(__FILE__) // page
      );

      add_settings_field(
        'header_scripts', // id
        esc_html('Opt-in scripts executed in <head>:', 'ezpz-cookies'), // title
        array($this, 'callback_optin_head_scripts'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_optin_scripts' // section
      );

      add_settings_field(
        'body_scripts', // id
        esc_html('Opt-in scripts executed in <body>:', 'ezpz-cookies'), // title
        array($this, 'callback_optin_body_scripts'), // callback
        plugin_basename(__FILE__), // page
        'ezpz_cookiebar_optin_scripts' // section
      );
    }

    /**
     * Callbacks for displaying form fields
     */
    public function callback_essential_head_scripts()
    {
      $options = $this->get_options();
      printf(
        '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[essential][header_scripts]" placeholder="%s">%s</textarea>',
        esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
        isset($options['essential']['header_scripts']) ? esc_attr($options['essential']['header_scripts']) : ''
      );
    }
    public function callback_essential_body_scripts()
    {
      $options = $this->get_options();
      printf(
        '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[essential][body_scripts]" placeholder="%s">%s</textarea>',
        esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
        isset($options['essential']['body_scripts']) ? esc_attr($options['essential']['body_scripts']) : ''
      );
    }
    public function callback_optin_head_scripts()
    {
      $options = $this->get_options();
      printf(
        '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[optin][header_scripts]" placeholder="%s">%s</textarea>',
        esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
        isset($options['optin']['header_scripts']) ? esc_attr($options['optin']['header_scripts']) : ''
      );
    }
    public function callback_optin_body_scripts()
    {
      $options = $this->get_options();
      printf(
        '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[optin][body_scripts]" placeholder="%s">%s</textarea>',
        esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
        isset($options['optin']['body_scripts']) ? esc_attr($options['optin']['body_scripts']) : ''
      );
    }

    public function callback_cookiebar_heading()
    {
      $options = $this->get_options('settings');
      printf(
        '<textarea rows="2" cols="80" name="ezpz_cookiebar_settings[text][cookie_bar_heading]">%s</textarea>',
        $options['text']['cookie_bar_heading']
      );
    }
    public function callback_cookiebar_message()
    {
      $options = $this->get_options('settings');
      echo wp_editor(
        $options['text']['cookie_bar_message'],
        'cookie_bar_message',
        $settings = array(
          'textarea_name' => 'ezpz_cookiebar_settings[text][cookie_bar_message]',
          'media_buttons' => false,
          'textarea_rows' => '10',
          'teeny' => true
        )
      );
    }
    public function callback_cookie_policy_page()
    {
      $options = $this->get_options('settings');
      echo '<select style="width:100%; max-width:300px;" name="ezpz_cookiebar_settings[cookies][policy_page]" placeholder="%s">';
      echo '<option value="0">---</option>';
      foreach (get_pages() as $page) {
        echo '<option value="' . $page->ID . '"' . ($page->ID == $options['cookies']['policy_page'] ? ' selected="selected"' : '') . '>' . $page->post_title . '</option>';
      }
      echo '</select>';
      echo '<p class="description">' . __('Please select the page on which the cookie policy is located. You can use the shortcode [ezpz-cookiebar] on that page to allow the user to adjust their preferences. The banner will not be displayed on this page.', 'ezpz-cookies') . '</p>';
    }
    public function callback_cookiebar_revoke()
    {
      $options = $this->get_options('settings');
      printf(
        '<textarea rows="2" cols="80" name="ezpz_cookiebar_settings[cookies][unset_on_revoke]" placeholder="%s">%s</textarea><p class="description">%s</p>',
        '__utma, __ga ...',
        $options['cookies']['unset_on_revoke'],
        __('Sometimes it is necessary to delete/unset cookies created by other scripts, when the user opts out of tracking cookies. Use this field to define those cookies, separated by a comma.', 'ezpz-cookies')
      );
    }
    public function callback_cookiebar_style()
    {
      $options = $this->get_options('settings');
      echo '<select style="width:100%; max-width:200px;" name="ezpz_cookiebar_settings[style]">';
      echo '<option value="intrusive"' . ('intrusive' == $options['style'] ? ' selected="selected"' : '') . '>' . __('Intrusive', 'ezpz-cookies') . '</option>';
      echo '<option value="unintrusive"' . ('unintrusive' == $options['style'] ? ' selected="selected"' : '') . '>' . __('Un-intrusive', 'ezpz-cookies') . '</option>';
      echo '</select>';
    }
    public function callback_cookiebar_toggle()
    {
      $options = $this->get_options('settings');
      printf(
        '<input type="checkbox" name="ezpz_cookiebar_settings[cookie_bar_active]" value="true" %s>',
        (isset($options['cookie_bar_active']) && $options['cookie_bar_active'] === 'true') ? 'checked' : ''
      );
    }
  }
}

/**
 * Initialize the plugin
 */
new EzpzCookies();

/**
 * Plugin Uninstallation
 * Clear settings
 */
register_uninstall_hook(__FILE__, 'ezpz_cookiebar_uninstall');
function ezpz_cookiebar_uninstall()
{
  delete_option('ezpz_cookiebar_scripts');
  delete_option('ezpz_cookiebar_settings');
}

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

 // TODO: Add the ability to tag third party scripts for dequeuing, eg. plugins.
 // TODO: The cache busting is still not 100%. If the entry page is cached, any subsequent visits back to that page wont get picked up in GA etc

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
	exit;
}

if ( ! class_exists('ezpz_cookies')) {

    class ezpz_cookies {

        private $ezpz_cookiebar_scripts = null;
        private $ezpz_cookiebar_settings = null;
        private $ezpz_cookie_prefs_name = null;
        private $ezpz_cookie_prefs_slug = null;

        /**
         * __construct() Sets up the class functionality
         */
        function __construct()
        {
          // Set up name for Cookie Bar based on Site URL
          $this->ezpz_cookie_prefs_slug = sanitize_title(get_bloginfo('name')) ? sanitize_title(get_bloginfo('name')) : 'epls';
          $this->ezpz_cookie_prefs_name = $this->ezpz_cookie_prefs_slug . '_cookie_prefs';

          // Retrieve Opts
          $this->ezpz_set_options();

          // Back End
          $this->ezpz_cookiebar_admin_init();

          // Front End
          $this->ezpz_cookiebar_init();

        }

        /**
         * Setters and Getters
         */
        private function ezpz_set_options(){
          $this->ezpz_cookiebar_scripts = get_option('ezpz_cookiebar_scripts');
          $this->ezpz_cookiebar_settings = get_option('ezpz_cookiebar_settings');
        }

        public function ezpz_get_options($type = 'scripts'){

          if($type == 'scripts') :
            return empty($this->ezpz_cookiebar_scripts) ? array() : apply_filters( 'ezpz_scripts', $this->ezpz_cookiebar_scripts );

          elseif($type == 'settings') :
            $settings = empty($this->ezpz_cookiebar_settings) ? array() : $this->ezpz_cookiebar_settings;

            // Set defaults
            if(!isset($settings['cookie_bar_message']) || empty($settings['cookie_bar_message'])) {
              $settings['cookie_bar_message'] = sprintf(
                '%s<a href="/cookies/" tabindex="3">%s</a>%s',
                 __( 'We use marketing and analytics cookies to help us better understand how visitors use our website and to improve the user experience. You can switch these cookies off if you would like. Read more about how we use cookies on our '),
                 __( 'cookie policy'),
                 __( ' page.'),
              );
            }
            if(!isset($settings['style'])|| empty($settings['style'])) $settings['style'] = 'intrusive';

            return apply_filters('ezpz_settings', $settings);

          else:
            return null;
          endif;
        }


        /**
         * Init Admin Dashboard
         */
        private function ezpz_cookiebar_admin_init() {
          add_action( 'admin_menu' , array($this, 'ezpz_cookiebar_admin_menu'));
          //add_action( 'admin_init' , array( $this, 'ezpz_cookiebar_settings_page_init' ) );
          add_action( 'admin_enqueue_scripts' , array( $this, 'ezpz_cookiebar_admin_css' ) );
        }

        /**
         * Initialize Cookiebar on the front end
         */
        private function ezpz_cookiebar_init() {
          // If Cookie Bar Is Active, enqueue JS, CSS and load view...
          if(get_option( 'ezpz_cookiebar_settings' )['cookie_bar_active'] && !isset($_COOKIE[$this->ezpz_cookie_prefs_name])) :
            add_action( 'wp_footer' , $this->ezpz_cookiebar_set_js_var($this->ezpz_cookie_prefs_name), 30);
            add_action( 'wp_footer' , array( $this, 'ezpz_cookiebar_display' ));
            add_action( 'wp_enqueue_scripts' , array( $this, 'ezpz_cookiebar_enqueue_css' ));
            add_action( 'wp_enqueue_scripts' , array( $this, 'ezpz_cookiebar_enqueue_js' ));
          endif;

          if(isset($_COOKIE[$this->ezpz_cookie_prefs_name]) && $_COOKIE[$this->ezpz_cookie_prefs_name] === 'accepted' && get_option( 'ezpz_cookiebar_settings' )['cookie_bar_active']) {
            add_action( 'wp_head' , array( $this, 'ezpz_cookiebar_render_essential_header_scripts' ), 100);
            add_action( 'wp_body_open' , array( $this, 'ezpz_cookiebar_render_essential_body_scripts' ));
          }
        }

        /**
         * Echo out the name of $this->ezpz_cookie_prefs_name as a var so that Javascript can use it
         */
        function ezpz_cookiebar_set_js_var($cookie_prefs_name) {
          $output =
          "<script type='text/javascript' id='cookiebar-preferences-var'>var cookiePrefsName = '".$this->ezpz_cookie_prefs_name."';</script>";
          $func = function () use($output) {
            print $output;
          };
          return $func;
        }

        /**
         * Display Cookie Bar
         */
        function ezpz_cookiebar_display()
        {
            require plugin_dir_path(__FILE__) . 'cookiebar-view.php';
        }

        /**
         * Enqueue CSS on Front End
         */
        function ezpz_cookiebar_enqueue_css()
        {
        	wp_enqueue_style('cookie-bar', plugins_url('cookie-bar.css', __FILE__), '', "1.2.0");
        }

        /**
         * Enqueue JS on Front End
         */
        function ezpz_cookiebar_enqueue_js()
        {
        	wp_enqueue_script('cookie-bar', plugins_url('cookie-bar.js', __FILE__), '', "1.2.0", true);
        }

        /**
         * Render Header Scripts
         */
        function ezpz_cookiebar_render_essential_header_scripts()
        {
          $scripts = get_option( 'ezpz_cookiebar_settings' )['essential_header_scripts'];
          echo html_entity_decode($scripts);
        }

        /**
         * Render Body Scripts
         */
        function ezpz_cookiebar_render_essential_body_scripts()
        {
          $scripts = get_option( 'ezpz_cookiebar_settings' )['essential_body_scripts'];
          echo html_entity_decode($scripts);
        }

        /**
         * Register Setting Groups
         */
        function ezpz_register_settings() {
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
        function ezpz_cookiebar_admin_menu() {
          add_options_page(
            __('Cookie Settings'), // Page Title
            __('Cookie Settings'), // Menu Title
            'manage_options', // Capability
            plugin_basename(__FILE__), // Menu Slug
            array($this, 'ezpz_cookiebar_output_admin_page'), // Function
          );
          $this->ezpz_register_settings();
        }

        /**
         * Render Admin Settings Page
         */
        public function ezpz_cookiebar_output_admin_page() {
          // check user capabilities
          if ( ! current_user_can( 'manage_options' ) ) {
            return;
          }

          //Get the active tab from the $_GET param
          $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'scripts';

          $tabs = [
            __('Scripts','ezpz-cookies')  => 'scripts',
            __('Settings','ezpz-cookies') => 'settings'
          ];

          ?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

                <!-- TODO: Add if updated message here -->

				        <h2 class="nav-tab-wrapper">
                  <?php foreach($tabs as $tab_name => $tab_slug):
                    $tab_class = 'nav-tab';
                    if($active_tab == $tab_slug) $tab_class .= ' nav-tab-active';
                    echo '<a href="?page='.plugin_basename(__FILE__).'&amp;tab='.$tab_slug.'" class="'.$tab_class.'">'.$tab_name.'</a>';
                  endforeach; ?>
				        </h2>
                <div class="tab-content">
                  <form method="post" action="options.php">
                    <?php switch($active_tab) :
                      case 'settings':
                        $this->ezpz_cookiebar_init_opts_settings();
                        settings_fields('ezpz_cookiebar_settings_group');
                        do_settings_sections( plugin_basename(__FILE__) );
                        break 1;
                      default:
                        $this->ezpz_cookiebar_init_opts_scripts();
                        settings_fields('ezpz_cookiebar_scripts_group');
                        do_settings_sections( plugin_basename(__FILE__) );
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
		function ezpz_cookiebar_admin_css($hook)
		{
            // Load only on ?page=ezpz-cookies
			if ($hook != 'settings_page_ezpz-cookies') {
				return;
            }
			wp_enqueue_style('cookiebar_admin_css', plugins_url('cookie-bar-admin.css', __FILE__));
		}





        public function ezpz_cookiebar_essential_scripts_section_info() {
          _e('The scripts you add here will always be executed; they are considered to be essential scripts and not opt-in/opt-out. Do not add marketing or tracking scripts here.');
        }

        public function ezpz_cookiebar_optin_scripts_section_info() {
          _e('The scripts you add here will only be executed if the user explicitly opts-in to tracking/analytics cookies.');
        }

        /**
         * Initializes options for the Settings tab
         */
        public function ezpz_cookiebar_init_opts_settings() {
          add_settings_section(
            'ezpz_cookiebar_settings', // id
            __('Settings', 'ezpz-cookies'), // title
            null, // callback
            plugin_basename(__FILE__) // page
          );

          add_settings_field(
              'cookie_bar_message', // id
              __( 'Cookie Bar Message', 'ezpz-cookies' ), // title
              array( $this, 'cookiebar_message_callback' ), // callback
              plugin_basename(__FILE__), // page
              'ezpz_cookiebar_settings' // section
          );


          add_settings_field(
            'cookie_bar_style', // id
            __('Cookie Bar Style', 'ezpz-cookies'), // title
            array( $this, 'cookiebar_style_callback' ), // callback
            plugin_basename(__FILE__), // page
            'ezpz_cookiebar_settings' // section
        );

          add_settings_field(
              'cookie_bar_active', // id
              __('Cookie Bar Active?', 'ezpz-cookies'), // title
              array( $this, 'cookiebar_toggle_callback' ), // callback
              plugin_basename(__FILE__), // page
              'ezpz_cookiebar_settings' // section
          );
        }

        /**
         * Initializes options for the Scripts tab
         */
        public function ezpz_cookiebar_init_opts_scripts() {

          add_settings_section(
            'ezpz_cookiebar_essential_scripts', // id
            __('Essential Scripts', 'ezpz-cookies'), // title
            array( $this, 'ezpz_cookiebar_essential_scripts_section_info' ), // callback
            plugin_basename(__FILE__) // page
          );

          add_settings_field(
            'essential_header_scripts', // id
            esc_html( 'Essential scripts executed in <head>:','ezpz-cookies' ), // title
            array( $this, 'essential_header_scripts_callback' ), // callback
            plugin_basename(__FILE__), // page
            'ezpz_cookiebar_essential_scripts' // section
          );

          add_settings_field(
              'essential_body_scripts', // id
              esc_html( 'Essential scripts executed in <body>:','ezpz-cookies' ), // title
              array( $this, 'essential_body_scripts_callback' ), // callback
              plugin_basename(__FILE__), // page
              'ezpz_cookiebar_essential_scripts' // section
          );

          add_settings_section(
            'ezpz_cookiebar_optin_scripts', // id
            __('Opt-In Scripts', 'ezpz-cookies'), // title
            array( $this, 'ezpz_cookiebar_optin_scripts_section_info' ), // callback
            plugin_basename(__FILE__) // page
          );

          add_settings_field(
            'header_scripts', // id
            esc_html( 'Opt-in scripts executed in <head>:','ezpz-cookies' ), // title
            array( $this, 'optin_header_scripts_callback' ), // callback
            plugin_basename(__FILE__), // page
            'ezpz_cookiebar_optin_scripts' // section
          );

          add_settings_field(
              'body_scripts', // id
              esc_html( 'Opt-in scripts executed in <body>:','ezpz-cookies' ), // title
              array( $this, 'optin_body_scripts_callback' ), // callback
              plugin_basename(__FILE__), // page
              'ezpz_cookiebar_optin_scripts' // section
          );

        }

        /**
         * Callbacks for displaying form fields
         */
        public function essential_header_scripts_callback() {
          $options = $this->ezpz_get_options();
          printf(
              '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[essential][header_scripts]" placeholder="%s">%s</textarea>',
              esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
              isset( $options['essential']['header_scripts'] ) ? esc_attr( $options['essential']['header_scripts']) : ''
          );
        }
        public function essential_body_scripts_callback() {
          $options = $this->ezpz_get_options();
          printf(
              '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[essential][body_scripts]" placeholder="%s">%s</textarea>',
              esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
              isset( $options['essential']['body_scripts'] ) ? esc_attr( $options['essential']['body_scripts']) : ''
          );
        }
        public function optin_header_scripts_callback() {
          $options = $this->ezpz_get_options();
          printf(
              '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[optin][header_scripts]" placeholder="%s">%s</textarea>',
              esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
              isset( $options['optin']['header_scripts'] ) ? esc_attr( $options['optin']['header_scripts']) : ''
          );
        }
        public function optin_body_scripts_callback() {
          $options = $this->ezpz_get_options();
          printf(
              '<textarea class="large-text code" rows="8" name="ezpz_cookiebar_scripts[optin][body_scripts]" placeholder="%s">%s</textarea>',
              esc_html('<script type=&quot;text/javascript&quot;>...</script>', 'ezpz-cookies'),
              isset( $options['optin']['body_scripts'] ) ? esc_attr( $options['optin']['body_scripts']) : ''
          );
        }

        public function cookiebar_message_callback() {
          $options = $this->ezpz_get_options('settings');
          var_dump($options);
            echo wp_editor(
              $options['cookie_bar_message'],
              'cookie_bar_message',
              $settings = array(
                'textarea_name' => 'ezpz_cookiebar_settings[cookie_bar_message]',
                'media_buttons' => false,
                'textarea_rows' => '10',
                'teeny' => true
              ) );
        }

        public function cookiebar_style_callback() {
          $options = $this->ezpz_get_options('settings');
          echo '<select style="width:100%; max-width:200px;" name="ezpz_cookiebar_settings[style]">';
          echo '<option value="intrusive"'.('intrusive' == $options['style'] ? ' selected="selected"' : '').'>'.__('Intrusive', 'ezpz-cookies').'</option>';
          echo '<option value="unintrusive"'.('unintrusive' == $options['style'] ? ' selected="selected"' : '').'>'.__('Un-intrusive', 'ezpz-cookies').'</option>';
          echo '</select>';
        }
        public function cookiebar_toggle_callback() {
          $options = $this->ezpz_get_options('settings');
          printf(
              '<input type="checkbox" name="ezpz_cookiebar_settings[cookie_bar_active]" value="true" %s>',
              ( isset( $options['cookie_bar_active'] ) && $options['cookie_bar_active'] === 'true' ) ? 'checked' : ''
          );
        }

    }
}

/**
 * Initialize the plugin
 */
new ezpz_cookies();

/**
 * Plugin Uninstallation
 * Clear settings
 *
 */

register_uninstall_hook( __FILE__, 'ezpz_cookiebar_uninstall' );
function ezpz_cookiebar_uninstall() {
	delete_option( 'ezpz_cookiebar_scripts' );
	delete_option( 'ezpz_cookiebar_settings' );
}

<?php

/**
 * Plugin Name:  EZPZ Cookie Bar
 * Plugin URI:   https://github.com/epls-design/ezpz-cookies/
 * Description:  Simple GDPR compliant cookie bar for Wordpress. Allows the website administrator to add analytics code to the header, footer and body via the dashboard. These tracking codes will only run on user acceptance of the cookie policy.
 * Version:      1.0.0
 * Author:       EPLS Design
 * Author URI:   https://epls.design
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:  ezpz-cookies
 */

 // TODO: Add the ability to tag third party scripts for dequeuing, eg. plugins.

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
	exit;
}

if ( ! class_exists('ezpz_cookies')) {

    class ezpz_cookies {

        private $ezpz_cookiebar_options;
        private $ezpz_cookie_prefs_name;

        /**
         * __construct() Sets up the class functionality
         */
        function __construct()
        {
          // Set up name for Cookie Bar based on Site URL
          $this->ezpz_cookie_prefs_slug = sanitize_title(get_bloginfo('name')) ? sanitize_title(get_bloginfo('name')) : 'epls';
          $this->ezpz_cookie_prefs_name = $this->ezpz_cookie_prefs_slug . '_cookie_prefs';

          $this->ezpz_cookiebar_init();
          $this->ezpz_cookiebar_admin_init();
        }

        /**
         * Init Admin Dashboard
         */
        private function ezpz_cookiebar_admin_init() {
          add_action( 'admin_menu' , array($this, 'ezpz_cookiebar_admin_menu'));
          add_action( 'admin_init' , array( $this, 'ezpz_cookiebar_settings_page_init' ) );
          add_action( 'admin_enqueue_scripts' , array( $this, 'ezpz_cookiebar_admin_css' ) );
        }

        /**
         * Initialize Cookiebar on the front end
         */
        private function ezpz_cookiebar_init() {
          // If Cookie Bar Is Active, enqueue JS, CSS and load view...
          if(get_option( 'ezpz_cookiebar_settings' )['cookie_bar_active'] && !isset($_COOKIE[$this->ezpz_cookie_prefs_name])) :
            add_action('wp_footer', $this->ezpz_cookiebar_set_js_var($this->ezpz_cookie_prefs_name), 10);
            add_action( 'wp_footer' , array( $this, 'ezpz_cookiebar_display' ));
            add_action( 'wp_enqueue_scripts' , array( $this, 'ezpz_cookiebar_enqueue_css' ), 0);
            add_action( 'wp_enqueue_scripts' , array( $this, 'ezpz_cookiebar_enqueue_js' ), 30);
          endif;

          if(isset($_COOKIE[$this->ezpz_cookie_prefs_name]) && $_COOKIE[$this->ezpz_cookie_prefs_name] === 'accepted' && get_option( 'ezpz_cookiebar_settings' )['cookie_bar_active']) {
            add_action( 'wp_head' , array( $this, 'ezpz_cookiebar_render_header_scripts' ), 100);
            add_action( 'wp_body_open' , array( $this, 'ezpz_cookiebar_render_body_scripts' ));
            add_action( 'wp_footer' , array( $this, 'ezpz_cookiebar_render_footer_scripts' ), 30);
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
        	wp_enqueue_style('cookie-bar', plugins_url('cookie-bar.css', __FILE__), '', "1.0.0");
        }

        /**
         * Enqueue JS on Front End
         */
        function ezpz_cookiebar_enqueue_js()
        {
        	wp_enqueue_script('cookie-bar', plugins_url('cookie-bar.js', __FILE__), '', "1.0.0", true);
        }

        /**
         * Render Header Scripts
         */
        function ezpz_cookiebar_render_header_scripts()
        {
          $scripts = get_option( 'ezpz_cookiebar_settings' )['header_scripts'];
          echo html_entity_decode($scripts);
        }

        /**
         * Render Body Scripts
         */
        function ezpz_cookiebar_render_body_scripts()
        {
          $scripts = get_option( 'ezpz_cookiebar_settings' )['body_scripts'];
          echo html_entity_decode($scripts);
        }

        /**
         * Render Footer Scripts
         */
        function ezpz_cookiebar_render_footer_scripts()
        {
          $scripts = get_option( 'ezpz_cookiebar_settings' )['footer_scripts'];
          echo html_entity_decode($scripts);
        }

        /**
         * Add Admin Menu Item
         */
        function ezpz_cookiebar_admin_menu() {
            add_options_page(
                __('Cookie Settings'), // Page Title
                __('Cookie Settings'), // Menu Title
                'manage_options', // Capability
                'ezpz-cookies', // Menu Slug
                array($this, 'ezpz_cookiebar_create_admin_page'), // Function
                );
        }

        /**
         * Render Admin Settings Page
         */
        public function ezpz_cookiebar_create_admin_page() {
            $this->ezpz_cookiebar_options = get_option( 'ezpz_cookiebar_settings' );
            ?>
            <div class="wrap">
                <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

                <form method="post" action="options.php">
                    <?php
                        settings_fields( 'ezpz_cookiebar_options_group' );
                        do_settings_sections( 'ezpz-cookiebar-settings' );
                        submit_button();
                    ?>
                </form>
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

        public function ezpz_cookiebar_scripts_section_info() {
            _e('The scripts you add here will only be enqueued if the user accepts tracking/analytics cookies. Your theme must support the wp_body_open() hook in order to display the Body Scripts.');
        }

        public function ezpz_cookiebar_opts_section_info() {
            // Return nothing
        }

        /**
         * Creates the settings form for ezpz_cookiebar_create_admin_page()
         */
        public function ezpz_cookiebar_settings_page_init() {

            register_setting(
                'ezpz_cookiebar_options_group', // option_group
                'ezpz_cookiebar_settings', // option_name
                // TODO: Add in sanitizing
            );

            add_settings_section(
                'ezpz_cookiebar_scripts', // id
                __('Scripts'), // title
                array( $this, 'ezpz_cookiebar_scripts_section_info' ), // callback
                'ezpz-cookiebar-settings' // page
            );

            add_settings_section(
                'ezpz_cookiebar_opts', // id
                __('Settings'), // title
                array( $this, 'ezpz_cookiebar_opts_section_info' ), // callback
                'ezpz-cookiebar-settings' // page
            );

            add_settings_field(
                'header_scripts', // id
                __( 'Header Scripts' ), // title
                array( $this, 'header_scripts_cb' ), // callback
                'ezpz-cookiebar-settings', // page
                'ezpz_cookiebar_scripts' // section
            );

            add_settings_field(
                'body_scripts', // id
                __( 'Body Scripts' ), // title
                array( $this, 'body_scripts_cb' ), // callback
                'ezpz-cookiebar-settings', // page
                'ezpz_cookiebar_scripts' // section
            );

            add_settings_field(
                'footer_scripts', // id
                __( 'Footer Scripts' ), // title
                array( $this, 'footer_scripts_cb' ), // callback
                'ezpz-cookiebar-settings', // page
                'ezpz_cookiebar_scripts' // section
            );

            add_settings_field(
                'cookie_bar_message', // id
                __( 'Cookie Bar Message' ), // title
                array( $this, 'cookiebar_message_cb' ), // callback
                'ezpz-cookiebar-settings', // page
                'ezpz_cookiebar_opts' // section
            );

            add_settings_field(
                'cookie_bar_active', // id
                __('Cookie Bar Active?'), // title
                array( $this, 'cookiebar_toggle_cb' ), // callback
                'ezpz-cookiebar-settings', // page
                'ezpz_cookiebar_opts' // section
            );
        }

        /**
         * Callback to display the field for Header Scripts
         */
        public function header_scripts_cb() {
            printf(
                '<textarea class="large-text code" rows="6" name="ezpz_cookiebar_settings[header_scripts]" id="header_scripts" placeholder="%s">%s</textarea>',
                __('Enter scripts to be include inside <head>'),
                isset( $this->ezpz_cookiebar_options['header_scripts'] ) ? esc_attr( $this->ezpz_cookiebar_options['header_scripts']) : ''
            );
        }

        /**
         * Callback to display the field for Body Scripts
         */
        public function body_scripts_cb() {
            printf(
                '<textarea class="large-text code" rows="6" name="ezpz_cookiebar_settings[body_scripts]" id="body_scripts" placeholder="%s">%s</textarea>',
                __('Enter scripts for inclusion after the opening <body> tag'),
                isset( $this->ezpz_cookiebar_options['body_scripts'] ) ? esc_attr( $this->ezpz_cookiebar_options['body_scripts']) : ''
            );
        }

        /**
         * Callback to display the field for Footer Scripts
         */
        public function footer_scripts_cb() {
            printf(
                '<textarea class="large-text code" rows="6" name="ezpz_cookiebar_settings[footer_scripts]" id="footer_scripts" placeholder="%s">%s</textarea>',
                __('Enter scripts for inclusion before the closing </body> tag'),
                isset( $this->ezpz_cookiebar_options['footer_scripts'] ) ? esc_attr( $this->ezpz_cookiebar_options['footer_scripts']) : ''
            );
        }

        /**
         * Callback to display the field for Cookie Bar Message
         */
        public function cookiebar_message_cb() {
            $default_cookie_notice = sprintf(
                '%s<a href="/cookies/" tabindex="3">%s</a>%s',
                 __( 'We use marketing and analytics cookies to help us better understand how visitors use our website and to improve the user experience. You can switch these cookies off if you would like. Read more about how we use cookies on our '),
                 __( 'cookie policy'),
                 __( ' page.'),
              );
              $cookie_bar_notice = isset( $this->ezpz_cookiebar_options['cookie_bar_message']) ? $this->ezpz_cookiebar_options['cookie_bar_message'] : $default_cookie_notice; // TODO: Not yet working.
              echo wp_editor(
                $cookie_bar_notice,
                'cookie_bar_message',
                $settings = array(
                  'textarea_name' => 'ezpz_cookiebar_settings[cookie_bar_message]',
                  'media_buttons' => false,
                  'textarea_rows' => '10',
                  'teeny' => true
                ) );
        }

        /**
         * Callback to display the checkbox to enable/disable the cookie bar
         */
        public function cookiebar_toggle_cb() {
            printf(
                '<input type="checkbox" name="ezpz_cookiebar_settings[cookie_bar_active]" id="cookie_bar_active" value="cookie_bar_active" %s>',
                ( isset( $this->ezpz_cookiebar_options['cookie_bar_active'] ) && $this->ezpz_cookiebar_options['cookie_bar_active'] === 'cookie_bar_active' ) ? 'checked' : ''
            );
        }

    }
}

/**
 * Initialize the plugin
 */
new ezpz_cookies();

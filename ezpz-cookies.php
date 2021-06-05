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

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
	exit;
}

if ( ! class_exists('ezpz_cookies')) {

    class ezpz_cookies {

        private $ezpz_cookiebar_options;

        /**
         * __construct() Sets up the class functionality
         */
        function __construct()
        {
            /**
             * Init Admin Dashboard
             */
            add_action('admin_menu', array($this, 'ezpz_cookiebar_admin_menu'));
            add_action( 'admin_init', array( $this, 'ezpz_cookiebar_settings_page_init' ) );
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


        public function ezpz_cookiebar_scripts_section_info() {
            echo 'The scripts you add here will only be enqueued if the user accepts tracking/analytics cookies.';
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
                '%s<a href="/cookies/">%s</a>%s',
                 __( 'We use various tracking cookies to help us better understand how visitors use our website and to improve the user experience. You can switch these cookies off if you would like. Read more about how we use cookies on our '),
                 __( 'cookie policy'),
                 __( ' page.'),
              );
              $cookie_bar_notice = isset( $this->ezpz_cookiebar_options['cookie_bar_message']) ? $this->ezpz_cookiebar_options['cookie_bar_message'] : $default_cookie_notice; // TODO: Not yet working.
              echo wp_editor(
                $cookie_bar_notice,
                'cookie_bar_message',
                $settings = array(
                  'textarea_name' => 'ezpz_cookiebar_settings[cookie_bar_message]',
                  'editor_class' => 'simple-wysiwig', // TODO: Use this class with display: none to hide unrequired buttons
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
if ( is_admin() )
    $ezpz_cookies = new ezpz_cookies();

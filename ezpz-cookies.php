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

if ( ! class_exists('ezpz_cookie_bar')) {

    class ezpz_cookie_bar {

        /**
         * __construct() Sets up the class functionality
         */
        function __construct()
        {
            /**
             * Init Admin Dashboard
             */
            add_action('admin_menu', [$this, 'cb_admin_menu']);
        }

        /**
         * Add Admin Menu Item
         */
        function cb_admin_menu() {
            add_options_page(
                __('Cookie Settings'),
                __('Cookie Settings'),
                'manage_options',
                'ezpz-cookies',
                [$this, 'cb_admin_page'],
                NULL);
        }

        /**
         * Render Admin Settings Page
         */
        public function cb_admin_page() {
            $settings_page = plugin_dir_path(__FILE__) . 'admin-settings.php';
            if (file_exists($settings_page)) require $settings_page;
            else echo '<h1>'.__('Admin Settings file does not exist').'</h1>';
        }
    }

    /**
     * Initialize the plugin
     */
    new ezpz_cookie_bar();

}
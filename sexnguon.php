<?php

/*
* @wordpress-plugin
* Plugin Name: SexNguon Crawler
* Plugin URI: https://sexnguon.com
* Description: Thu thập phim từ SexNguon - Tương thích theme HaLimMovie
* Version: 2.0.1
* Requires PHP: 7.4^
* Author: Brevis Nguyen
* Author URI: https://github.com/brevis-ng
*/

// Protect plugins from direct access. If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die('Hành động chưa được xác thực!');
}

/**
 * Currently plugin version.
 * Start at version 1.0.0
 */
define( 'SEXNGUON_VERSION', '2.0.1' );

/**
 * The unique identifier of this plugin.
 */
set_time_limit(0);
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_sexnguon() {
    // Code
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_sexnguon() {
    // Code
}

register_activation_hook( __FILE__, 'activate_sexnguon' );
register_deactivation_hook( __FILE__, 'deactivate_sexnguon' );

/**
 * Provide a public-facing view for the plugin
 */
function movies_crawler_add_menu() {
    add_menu_page(
        __('SexNguon Crawler', 'textdomain'),
        'SexNguon Crawler',
        'manage_options',
        'sexnguon-crawler-tools',
        'sexnguon_crawler_page_menu',
        'dashicons-buddicons-replies',
        3
    );
}

/**
 * Include the following files that make up the plugin
 */
function sexnguon_crawler_page_menu() {
    require_once plugin_dir_path(__FILE__) . 'public/partials/sexnguon_view.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 * 
 */
require_once plugin_dir_path( __FILE__ ) . 'public/sexnguon_crawler.php';
function run_sexnguon_crawl() {
    add_action('admin_menu', 'movies_crawler_add_menu');

    $plugin_admin = new SexNguon_Crawler( 'sexnguon-crawler', SEXNGUON_VERSION );
    add_action('in_admin_header', array($plugin_admin, 'sexnguon_enqueue_scripts'));
    add_action('in_admin_header', array($plugin_admin, 'sexnguon_enqueue_styles'));

    add_action('wp_ajax_sexnguon_crawler_api', array($plugin_admin, 'sexnguon_crawler_api'));
    add_action('wp_ajax_sexnguon_get_movies_page', array($plugin_admin, 'sexnguon_get_movies_page'));
    add_action('wp_ajax_sexnguon_crawl_by_id', array($plugin_admin, 'sexnguon_crawl_by_id'));
}
run_sexnguon_crawl();
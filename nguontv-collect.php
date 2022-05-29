<?php
	/*
	 * Plugin Name: NguonTV Collect Plugin
	 * @copyright 2019 Breivs
	 * @wordpress-plugin
	 * Plugin URI: https://nguon.tv
	 * Description: Thu thập phim từ NguonTV - Tương thích theme HaLimMovie
	 * Version: 1.0.1
	 * Requires PHP: 7.4^
	 * Author: brevis
	 * Author URI: https://github.com/brevis-ng
	 */
	defined('ABSPATH') or die('This file can not be loaded directly.');
	@define('WL_PATH', dirname(__FILE__));
	
	global $wpdb;
	/* Enable plug-ins */
	function plugin_activate() {

	}
	register_activation_hook(__FILE__, 'plugin_activate');
	add_action('admin_menu', 'plugin_add_page',8);
	function plugin_add_page() {
		$plugin_page = add_menu_page(__('NguonTV','s-p-m'),__('Thu thập NguonTV','s-l-t'),'manage_options','thu-thap-nguontv','main_collect');
		add_action( 'admin_head-'. $plugin_page, 'add_plugin_favicon' );
	}

	function main_collect() {
		global $wpdb;
		require_once('splt-view.php');
		if($_POST['deleteall']=='Xóa Tất Cả'){
			$dkm1=$wpdb->prefix.'postmeta ';
			$dkm2=$wpdb->prefix.'posts';
			$dkm3=$wpdb->prefix.'terms ';
			$dkm4=$wpdb->prefix.'term_relationships ';
			$dkm5=$wpdb->prefix.'term_taxonomy ';
			
			$wpdb->query("TRUNCATE TABLE $dkm1");
			$wpdb->query("TRUNCATE TABLE $dkm2");
			$wpdb->query("TRUNCATE TABLE $dkm3");
			$wpdb->query("TRUNCATE TABLE $dkm4");
			$wpdb->query("TRUNCATE TABLE $dkm5");
			echo '<div class="updated"><p>Xóa Tất Cả Thành Công</p></div>';
		}		
		echo '<form method="POST" class="alignright"><br><input class="button-primary" type="submit" name="deleteall" value="Xóa Tất Cả"><br>
		</form>';
	}
	function add_plugin_favicon()
	{
		$output='<link id="favicon" rel="shortcut icon" href="" title="favicon"/>';
		echo $output;
	}
	if ( ! function_exists('add_director_halim') ) {
		function add_director_halim()
		{
			// $taxonomy = cs_get_option('taxonomy-director');
			$args = array(
    		'labels' => array(
			'name'          => 'Directors',
			'singular'      => 'Directors',
			'menu-name'     => 'Directors',
			'all_item'      => 'All Directors',
			'add_new_item'  => 'Add new director',
			),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'show_in_nav_menus' => true
			);
			register_taxonomy('director', 'post', $args);
		}
		add_action('init', 'add_director_halim', 0);
	}
	if ( ! function_exists('add_actor_halim') ) {
		function add_actor_halim()
		{
			// $taxonomy = cs_get_option('taxonomy-actor');
			$args = array(
    		'labels'            => array(
			'name'          => 'Actors',
			'singular'      => 'Actors',
			'menu-name'     => 'Actors',
			'all_item'      => 'All actors',
			'add_new_item'  => 'Add new actor',
    		),
    		'hierarchical'      => false,
    		'public'            => true,
    		'show_ui'           => true,
    		'show_admin_column' => true,
    		'show_tagcloud'     => true,
    		'show_in_nav_menus' => true,
            'show_in_rest'      => true,
			);
			
			register_taxonomy('actor', 'post', $args);
			
		}
		add_action('init', 'add_actor_halim', 0);
	}
	if ( ! function_exists('add_year_halim') ) {	
		function add_year_halim()
		{
			// $taxonomy = cs_get_option('taxonomy-release');
			$args = array(
    		'labels' => array(
			'name'          => 'Release',
			'singular'      => 'Release',
			'menu-name'     => 'Release',
			'all_item'      => 'View all',
			'add_new_item'  => 'Add new',
    		),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'show_in_nav_menus' => true
			);
			
			register_taxonomy('release', 'post', $args);
		}
		add_action('init', 'add_year_halim', 0);
	}
	if ( ! function_exists('add_country_halim') ) {	
		function add_country_halim()
		{
			// $taxonomy = cs_get_option('taxonomy-country');
			$args = array(
    		'labels' => array(
			'name'          => 'Country',
			'singular'      => 'Country',
			'menu-name'     => 'Country',
			'all_item'      => 'View all',
			'add_new_item'  => 'Add new country',
        	),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => true,
            'show_in_nav_menus' => true
			);
			
			register_taxonomy('country', 'post', $args);
		}
		add_action('init', 'add_country_halim', 0);
		
	}

?>
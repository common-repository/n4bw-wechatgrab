<?php
if(!function_exists('n4b_add_extra')) {
	add_filter('extra_plugin_headers','n4b_add_extra');
	add_filter('extra_theme_headers','n4b_add_extra');
	function n4b_add_extra($arr){
		$arr['N4BVER'] = 'N4B Version';
		return $arr;
	}
}
if(!function_exists('n4b_check_plugins')) {
	add_filter('admin_init','n4b_check_plugins');
	function n4b_check_plugins(){
		$plugins 	= get_plugins();
		$theme 	= wp_get_theme();
		$var = $dir = '';
		if($theme->get('N4B Version') != '' && (float)$theme->get('N4B Version') > 0){
			$var = (float)$theme->get('N4B Version');
			$dir = get_stylesheet_directory();
		}
		foreach ($plugins as $key => $plugin) {
			if($plugin['N4B Version'] != '' && (float)$plugin['N4B Version'] > $var){
				$var = (float)$plugin['N4B Version'];
				$dir = preg_replace('/(.*)\/{1}([^\/]*)/i', '$1', WP_PLUGIN_DIR.'/'.$key);
			}
		}
		define('N4B_PLUGIN_DIR',$dir);
	}
}
if(!function_exists('n4b_menu')) {
	add_action('admin_menu', 'n4b_menu');
	function n4b_menu(){
	    add_menu_page( 'N4B 套件', 'N4B 设置', 'edit_themes', 'n4b_admin','n4b_adminpage','',81);
	    $pages = apply_filters('n4b_setting_add_page',array());
	    foreach ($pages as $page) {
	    	add_submenu_page('n4b_admin',$page['menu_title'],'↳ '.$page['menu_title'],'edit_themes',$page['menu_slug'],'n4b_redirect');
	    }
	}   
	function n4b_adminpage(){
		include (N4B_PLUGIN_DIR .'/n4b/admin.php');
	}
	function n4b_redirect(){
		$url = admin_url('admin.php?page=n4b_admin&settingpage='.$_GET['page']);
		echo "<script>window.location.href='{$url}';</script>";
		exit;
	}
}

function n4b_load_plugins($plugins){
	foreach ($plugins as $plugin) {
		require_once("plugins/{$plugin}.php");
	}
}
?>
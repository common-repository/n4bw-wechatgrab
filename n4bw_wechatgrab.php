<?php
/*
Plugin Name:	N4BW WechatGrab
Plugin URI:		https://nnnn.blog/n4bw-wechatgrab.html
Description:	WordPress 微信采集助手
Version:		1.1.3
Author:			@快叫我韩大人
Author URI:  	https://nnnn.blog/
License:     	GPL2
License URI: 	https://www.gnu.org/licenses/gpl-2.0.html
N4B Version:	2.1

N4BW WechatGrab is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
N4BW WechatGrab is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with N4BW WechatGrab. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

define('N4BW_PLUGIN_URL', plugins_url('', __FILE__));
define('N4BW_PLUGIN_DIR', plugin_dir_path(__FILE__));


function n4bwCheckPHPVer(){
	if(version_compare(PHP_VERSION, '5.3.0') < 0){
		add_settings_error(
	        '插件激活失败',
	        esc_attr('settings_updated'),
	        '插件激活失败,您的 PHP 版本('.PHP_VERSION.')过低,需在 PHP 5.3.0 以上版本方可运行!'
    	);
    	settings_errors();
		exit;
	}
}
register_activation_hook( __FILE__,'n4bwCheckPHPVer');

spl_autoload_register(function($className){
	$classArr = preg_split("/(?=[A-Z])/", $className);
	$className = '';
	for ($i=1; $i < count($classArr); $i++) {
		$className .= $i == 1 ? strtolower($classArr[$i]) : '.'.strtolower($classArr[$i]);
	}
	$path = N4BW_PLUGIN_DIR.'class/'.$className . '.class.php';
	if(file_exists($path)){
		include_once $path;
		return true;
	}
	return false;
});
/*
	核心
 */
global $n4bw_default;
$n4bw_default = array(
	'n4bw_save_image' 	=> 'save',
	'n4bw_save_cover' 	=> 'cover',
	'n4bw_save_video'	=> 'auto',
	'n4bw_save_comment' => 'open',
	'n4bw_save_author' 	=> 'current',
	'n4bw_save_authorid'=> '',
	'n4bw_save_poststatus'	=> 'publish',
	'n4bw_save_style'	=> 'reset',
	'n4bw_save_category'=> array(),
	'n4bw_save_meta_author' => 'n4bw_author',
	'n4bw_save_meta_desc' 	=> 'n4bw_desc',
	'n4bw_save_meta_views'	=> 'views',
	'n4bw_save_meta_likes'	=> 'n4bw_likes',
	'n4bw_save_meta_sourceurl'	=> 'n4bw_sourceurl',
	'n4bw_grab_date'	=> 'manual'
);
// 加载Desk
if(!is_admin()){
	require_once(N4BW_PLUGIN_DIR.'desk/load.php');
}else{
	require_once(N4BW_PLUGIN_DIR.'admin/load.php');
}
// ADMIN
require_once('n4b/load.php');
add_filter('n4b_setting_add_page','n4bw_add_settingpage',10,1);
function n4bw_add_settingpage($pageArr){
	$pageArr[] = array(
		'page_title'		=> 'N4BW 微信采集助手',
		'menu_title'		=> '微信采集助手',
		'menu_slug'			=> 'wechatgrab',
		'child_slug'		=> array(
			'grab'	=> array(
				'function'		=>	'n4bw_grab_page',
				'menu_title' 	=>	'采集队列',
				'init'			=>  N4BW_PLUGIN_DIR.'admin/page.init.php',
				'page'			=>  N4BW_PLUGIN_DIR.'admin/page.grab.php'
			),
			// 'official-accounts'	=> array(
			// 	'function'		=>	'n4bw_official_accounts_page',
			// 	'menu_title' 	=>	'公众号',
			// 	'init'			=>  N4BW_PLUGIN_DIR.'admin/page.init.php',
			// 	'page'			=>  N4BW_PLUGIN_DIR.'admin/page.official-accounts.php'
			// ),
			'single'	=> array(
				'function'		=>	'n4bw_single_page',
				'menu_title' 	=>	'文章',
				'init'			=>  N4BW_PLUGIN_DIR.'admin/page.init.php',
				'page'			=>  N4BW_PLUGIN_DIR.'admin/page.single.php'
			)
		)
	);
	return $pageArr;
}
?>
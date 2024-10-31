<?php
wp_enqueue_script('n4bw_wechatgrab_js',N4BW_PLUGIN_URL.'/admin/resource/n4bw.admin.min.js',array('jquery-core'));
if(isset($_POST['n4b_save_field']) && wp_verify_nonce($_POST['n4b_save_field'],'n4b_save')){
	global $n4bw_default;
	if($_POST['submit'] == 'reset'){
		foreach ($_POST as $key => $value) {
			if(strstr($key,'n4bw_')){
				update_option($key,$n4bw_default[$key]);
			}
		}
		add_settings_error(
	        '初始化成功',
	        esc_attr('settings_updated'),
	        '已成功初始化当前页面的设置',
	        'updated'
    	);
		goto end;
	}elseif($_POST['submit'] == 'save'){
		foreach ($_POST as $key => $value) {
			if(strstr($key,'n4bw_')){
				update_option($key,$value);
			}
		}
		add_settings_error(
	        '保存成功',
	        esc_attr('settings_updated'),
	        '已成功保存当前页面的设置',
	        'updated'
    	);
		goto end;
	}
	end:
	wp_cache_delete('n4bw_data');
}
?>
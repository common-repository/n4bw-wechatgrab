<?php
    global $n4bw_data,$n4bw_default;
    if(wp_cache_get('n4bw_data') === false){
        $n4bw_data = $n4bw_default;
        foreach ($n4bw_data as $key => $value) {
            if(get_option($key)){
                $n4bw_data[$key] = get_option($key);
            } 
        }
        wp_cache_set('n4bw_data',$n4bw_data);
    }else{
        $n4bw_data = wp_cache_get('n4bw_data');
    }

    // 公共函数
    require_once('public.func.php');
?>
<?php
    // add_action( 'wp_head','n4bw_add_resetwechat',99,1);
add_action( 'wp_enqueue_scripts', 'n4bw_add_resetwechat' );
    function n4bw_add_resetwechat($content){
        $url = N4BW_PLUGIN_URL.'/desk/';
        // 注册ResteWechat
        wp_enqueue_style('n4bw_wechatgrab_css',$url.'resource/n4bw.min.css');
        wp_enqueue_script('n4bw_wechatgrab_js',$url.'resource/n4bw.min.js',array('jquery-core'));
        wp_localize_script('n4bw_wechatgrab_js', 'n4bw_local', array(
            'ajax_url' => admin_url('admin-ajax.php', (is_ssl() ? 'https' : 'http'))
        ));
        return $content;
    }

    function n4bwClearContenetP() {
        global $post;
        if (get_post_meta($post->ID,'_n4bw_type')){
            remove_filter('the_content', 'wpautop'); 
        }
    }
    add_action ('loop_start', 'n4bwClearContenetP');
?>
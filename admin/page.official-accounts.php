<?php
function n4bw_official_accounts_page(){
	global $n4bw_default;
	$data = $n4bw_default;
	foreach ($data as $key => $value) {
		if(get_option($key)){
			$value = get_option($key);
			$data[$key] = is_string( $value ) ? stripslashes( $value ) : $value;
		}
	}
?>
<div class="n4b-highlight-box n4b-fullpage">
	<div class="n4b-highleft">
		<h4>插件声明</h4>
		<p>该插件抓取的任何内容,都属于插件使用者个人行为,造成的任何问题与插件作者无关</p>
	</div>
</div>
<?php settings_errors(); ?>
<form method="post">
	<div class="n4b-t">
		<h4>公众号设置</h4>
	</div>
	<table class="form-table">
		<tbody>
		</tbody>
	</table>
	<div class="n4b-foot">
		<?php wp_nonce_field('n4b_save', 'n4b_save_field'); ?>
		<button type="submit" class="button button-primary" name="submit" value="save">保存以上更改</button>
		<div class="r">
			<button type="submit" class="button" name="submit" value="reset" >初始化本页设置</button>
		</div>
	</div>
</form>
<?php }
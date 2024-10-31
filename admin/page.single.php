<?php
function n4bw_single_page(){
	global $n4bw_default;
	$data = $n4bw_default;
	foreach ($data as $key => $value) {
		if(get_option($key)){
			$value = get_option($key);
			$data[$key] = is_string( $value ) ? stripslashes( $value ) : $value;
		}
	}
	if(isset($_POST['submit']) && $_POST['submit'] != 'reset' && isset($_POST['n4b_run_field']) && wp_verify_nonce($_POST['n4b_run_field'],'n4b_run')){
		$post_date = "{$_POST['aa']}-{$_POST['mm']}-{$_POST['jj']} {$_POST['hh']}:{$_POST['mn']}:59";
		$data['post_date'] = $post_date;
		// 拼接正确的 URL 地址
		$urls = explode("\n",trim($_POST['posturls']));
		$url_data = array();
		foreach ($urls as $url) {
			$url_data[] = array('status' => 0,'url' => $url);
		}

		if(is_array($urls) && count($urls) > 0){
			$data['posturls'] = $url_data;
			$n4bwgrab = new N4bwGrab();
			$result = $n4bwgrab->createGrab(array('event' => $data,'type' => 2));
			if(is_wp_error($result)){
				add_settings_error(
			        '创建失败',
			        esc_attr('settings_updated'),
			        $result->get_error_message()
		    	);	
			}else{
				add_settings_error(
			        '创建成功',
			        esc_attr('settings_updated'),
			        '已成功添加任务至队列',
			        'updated'
		    	);
		    	// 跳转至队列
				$url = admin_url('admin.php?page=n4b_admin&settingpage=wechatgrab&childpage=grab');
				echo "<script>window.location.href='{$url}';</script>";
			}
				
		}

	}
?>
<?php settings_errors(); ?>
<form method="post">
	<div class="n4b-t">
		<h4>文章抓取</h4>
	</div>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">公众号文章 URL</th>
				<td>
					<textarea rows="10" cols="50" class="large-text code" name="posturls"><?php if(isset($_POST['posturls'])) echo esc_textarea($_POST['posturls']); ?></textarea>
					<p>多篇文章使用换行区分,一行一个.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">图像资源</th>
				<td>
				<select name="n4bw_save_image">
					<option <?php if($data['n4bw_save_image'] == 'save') echo 'selected="selected"'; ?> value="save">将图像保存至本地(推荐)</option>
					<option <?php if($data['n4bw_save_image'] == 'link') echo 'selected="selected"'; ?> value="link">使用远程地址</option>
				</select>
				<p>建议将图像内容保存到本地,以免原地址失效.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">特色图像</th>
				<td>
				<select name="n4bw_save_cover">
					<option <?php if($data['n4bw_save_cover'] == 'cover') echo 'selected="selected"'; ?> value="cover">使用封面(推荐)</option>
					<option <?php if($data['n4bw_save_cover'] == 'first') echo 'selected="selected"'; ?> value="first">使用文章第一张图片</option>
					<option <?php if($data['n4bw_save_cover'] == 'close') echo 'selected="selected"'; ?> value="close">不设置</option>
				</select>
				<p>封面图片将自动保存到本地.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">视频适配</th>
				<td>
				<select name="n4bw_save_video">
					<option <?php if($data['n4bw_save_video'] == 'auto') echo 'selected="selected"'; ?> value="auto">宽度100%,高度自适应</option>
					<option <?php if($data['n4bw_save_video'] == 'default') echo 'selected="selected"'; ?> value="default">使用默认设置</option>
				</select>
				<p>推荐使用自适应,因为通常情况下,使用默认设置会导致视频出现尺寸过大或过小的情况</p>
				</td>
			</tr>
	<!-- 		<tr>
				<th scope="row">导入评论(不稳定,暂不可用)</th>
				<td>
				<select name="n4bw_save_comment">
					<option <?php //if($data['n4bw_save_comment'] == 'open') echo 'selected="selected"'; ?> value="open">导入评论(如果有)</option>
					<option <?php //if($data['n4bw_save_comment'] == 'close') echo 'selected="selected"'; ?> value="close">关闭</option>
				</select>
				<p>如果有,将导入该公众号的评论信息至文章评论中去.</p>
				<p>如果你安装了我们提供的 <a href="#">N4BC 评论框插件</a> ,它支持导入公众号文章评论点赞数/用户名/头像.</p>
				</td>
			</tr> -->
			<tr>
				<th scope="row">作者</th>
				<td>
				<select data-bind-select='[{"event":"change","if":"custom","bind":"#authorid"}]' name="n4bw_save_author">
					<option  <?php if($data['n4bw_save_author'] == 'current') echo 'selected="selected"'; ?> value="current">当前登录用户</option>
					<option <?php if($data['n4bw_save_author'] == 'custom') echo 'selected="selected"'; ?> value="custom">自定义作者</option>
				</select>
				<p>如选择自定义作者,请填写下方的作者 ID</p>
				</td>
			</tr>
			<tr id="authorid" class="<?php if($data['n4bw_save_author'] != 'custom') esc_attr_e('n4b-none'); ?>">
				<th scope="row">自定义作者</th>
				<td>
				<input name="n4bw_save_authorid" placeholder="填写用户 ID" type="text" class="regular-text code"  value="<?php echo esc_attr($data['n4bw_save_authorid']); ?>">
				<p>留空为当前登录用户</p>
				</td>
			</tr>
			<tr>
				<th scope="row">文章状态</th>
				<td>
				<select data-bind-select='[{"event":"change","if":"future","bind":"#timestamp"}]' name="n4bw_save_poststatus">
					<option <?php if($data['n4bw_save_poststatus'] == 'publish') echo 'selected="selected"'; ?> value="publish">已发布</option>
					<option <?php if($data['n4bw_save_poststatus'] == 'private') echo 'selected="selected"'; ?> value="private">私有的</option>
					<option <?php if($data['n4bw_save_poststatus'] == 'pending') echo 'selected="selected"'; ?> value="pending">等待复审</option>
					<option <?php if($data['n4bw_save_poststatus'] == 'draft') echo 'selected="selected"'; ?> value="draft">草稿</option>
					<option <?php if($data['n4bw_save_poststatus'] == 'future') echo 'selected="selected"'; ?> value="future">定时发布</option>
				</select>
				<p>如选择定时发布,请填写下方的定时时间</p>
				</td>
			</tr>
			<tr id="timestamp" class="<?php if($data['n4bw_save_poststatus'] != 'future') esc_attr_e('n4b-none'); ?>">
				<th scope="row">定时发布</th>
				<td>
					<?php $time_adj = current_time('timestamp'); ?>
					<div class="timestamp-wrap">
						<label>
							<span class="screen-reader-text">年</span>
							<input type="text" id="aa" name="aa" value="<?php esc_attr_e(gmdate( 'Y', $time_adj )); ?>" size="4" maxlength="4" autocomplete="off">
						</label>-<label>
							<span class="screen-reader-text">月份</span>
							<select id="mm" name="mm">
								<?php
									$m = (int)gmdate('m',$time_adj);
									for ($i = 1; $i <= 12; $i++) {
										$s = $t = '';
										if($i < 10)
											$t = '0';

										if($m == $i){
											$s = 'selected="selected"';
										}
										echo "<option value='{$t}{$i}' {$s} data-text='{$i}月'>{$i}月</option>";
									}
								?>
							</select>
						</label>-<label>
							<span class="screen-reader-text">日期</span>
							<input type="text" id="jj" name="jj" value="<?php esc_attr_e(gmdate( 'd', $time_adj )); ?>" size="2" maxlength="2" autocomplete="off">
						</label> @ <label>
							<span class="screen-reader-text">小时</span>
							<input type="text" id="hh" name="hh" value="<?php esc_attr_e(gmdate( 'H', $time_adj )); ?>" size="2" maxlength="2" autocomplete="off">
						</label>:<label>
							<span class="screen-reader-text">分钟</span>
							<input type="text" id="mn" name="mn" value="<?php esc_attr_e(gmdate( 'i', $time_adj )); ?>" size="2" maxlength="2" autocomplete="off">
						</label>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">样式重置</th>
				<td>
				<select name="n4bw_save_style">
					<option <?php if($data['n4bw_save_style'] == 'reset') echo 'selected="selected"'; ?> value="reset">重置样式</option>
					<option <?php if($data['n4bw_save_style'] == 'close') echo 'selected="selected"'; ?> value="close">关闭重置</option>
				</select>
				<p>部分主题文章样式可能会影响微信文章样式,导致内容错位等情况(可自行修改).启用样式重置时,将初始化微信文章内容为正常显示状态</p>
				</td>
			</tr>
			<tr>
				<th scope="row">分类设置</th>
				<td>
				<div class="n4b-cats">
					<ul>
						<?php wp_category_checklist(0,0,$data['n4bw_save_category'],false,new N4bwWalkerCategoryChecklist); ?>
					</ul>
				</div>
				</td>
			</tr>
			<tr>
				<th colspan="2">
					<div class="n4b-t">
						<h4>自定义栏目<span>调用:<code>get_post_meta('自定义栏目名称',$post->ID,true);</code></span></h4>
					</div>
				</th>
			</tr>
			<tr>
				<th scope="row">文章作者</th>
				<td>
				<input name="n4bw_save_meta_author" placeholder="自定义栏目名称" type="text" class="regular-text code"  value="<?php echo esc_attr($data['n4bw_save_meta_author']); ?>">
				<p>填写自定义栏目名称,会将公众号文章作者名称填入该自定义栏目,如果不需可不填.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">文章描述</th>
				<td>
				<input name="n4bw_save_meta_desc" placeholder="自定义栏目名称" type="text" class="regular-text code"  value="<?php echo esc_attr($data['n4bw_save_meta_desc']); ?>">
				<p>填写自定义栏目名称,会将公众号文章作者名称填入该自定义栏目,如果不需可不填.</p>
				</td>
		<!-- 	</tr>
			<tr>
				<th scope="row">文章阅读数</th>
				<td>
				<input name="n4bw_save_meta_views" placeholder="自定义栏目名称" type="text" class="regular-text code"  value="<?php // echo esc_attr($data['n4bw_save_meta_views']); ?>">
				<p>虽大部分用户的文章阅读数都采用 views ,请自行检查你的文章阅读数统计名称.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">文章点赞数</th>
				<td>
				<input name="n4bw_save_meta_likes" placeholder="自定义栏目名称" type="text" class="regular-text code"  value="<?php // echo esc_attr($data['n4bw_save_meta_likes']); ?>">
				<p>如果你不需要统计文章点赞数,可以不填写此处,或者采集后日后再用.</p>
				</td>
			</tr> -->
			<tr>
				<th scope="row">阅读原文</th>
				<td>
				<input name="n4bw_save_meta_sourceurl" placeholder="自定义栏目名称" type="text" class="regular-text code"  value="<?php echo esc_attr($data['n4bw_save_meta_sourceurl']); ?>">
				<p>如果有阅读原文将会采集到这个自定义栏目中.</p>
				</td>
			</tr>
			<tr>
				<th colspan="2">
					<div class="n4b-t">
						<h4>采集设置<span>建议在采集队列中手动启动采集</span></h4>
					</div>
				</th>
			</tr>
			<tr>
				<th scope="row">采集时间</th>
				<td>
				<select name="n4bw_grab_date">
					<option <?php if($data['n4bw_grab_date'] == 'manual') echo 'selected="selected"'; ?> value="manual">手动启动采集</option>
				</select>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="n4b-foot">
		<?php wp_nonce_field('n4b_run', 'n4b_run_field'); ?>
		<?php wp_nonce_field('n4b_save', 'n4b_save_field'); ?>
		<button type="submit" class="button" name="submit" value="reset" >初始化本页设置</button>
		<div class="r">
			<button type="submit" class="button button-primary" name="submit" value="save">创建采集任务</button>
		</div>
	</div>
</form>
<?php }
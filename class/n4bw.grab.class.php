<?php
	class N4bwGrab{
		/**
		 * 数据表名称
		 * @var string
		 */
		public $n4bw_table;

		public $wp_error;

		public function __construct(){
			// 引入插件
			n4b_load_plugins(array('ParserDom'));
			/**
			 * 检查或创建数据表
			 */
			global $wpdb;
			$this->n4bw_table = $wpdb->prefix . 'n4bwgrab';
			$this->checkGrabTable();

			$this->wp_error = new WP_Error();
		}

		/**
		 * 创建一篇文章
		 * @Author @快叫我韩大人
		 * @param  array   $event  文章事件
		 * @param  string  $post   采集的文章数组
		 * @return boolean         成功返回 true, 错误返回错误信息
		 */
		public function createPost($event,$post){
			$args = array();
			// 作者
			if($event['n4bw_save_author'] == 'custom' && is_user_member_of_blog($event['n4bw_save_authorid']))
				$args['post_author'] = $event['n4bw_save_authorid'];
			// 文章状态
			$args['post_status'] = $event['n4bw_save_poststatus'];

			// 时间
			if($event['n4bw_save_poststatus'] == 'future'){
				$args['post_status'] = 'publish';
				$args['post_date'] = $event['post_date'];
			}

			$args['post_content'] = $this->getPostContent($post['post_content'],$event);
			if($event['n4bw_save_style'] == 'reset')
				$args['post_content'] = "<div id='n4bw_resetwechar'>{$args['post_content']}</div>";

			$args['post_title'] = $post['post_title'];

			$args['post_category'] = $event['n4bw_save_category'];

			$args['meta_input'] = array();

			if($event['n4bw_save_meta_author'] != '')
				$args['meta_input'][$event['n4bw_save_meta_author']] = $post['author'];

			if($event['n4bw_save_meta_desc'] != '')
				$args['meta_input'][$event['n4bw_save_meta_desc']] = $post['desc'];

			if($event['n4bw_save_meta_views'] != '')
				$args['meta_input'][$event['n4bw_save_meta_views']] = $post['views'];

			if($event['n4bw_save_meta_likes'] != '')
				$args['meta_input'][$event['n4bw_save_meta_likes']] = $post['likes'];

			if($event['n4bw_save_meta_sourceurl'] != '')
				$args['meta_input'][$event['n4bw_save_meta_sourceurl']] = $post['sourceurl'];

			$args['meta_input']['_n4bw_type'] = 'wechat';

			$request = wp_insert_post($args,true);
			if(is_wp_error($request))
				return $request->get_error_message();

			$postID = $request;
			if($event['n4bw_save_cover'] == 'cover'){
				if($post['sourceurl'] != ''){
					$sourceurl = $this->saveRemoteImage($post['sourceurl']);
					if(!$sourceurl){
						set_post_thumbnail($postID,$sourceurl['attach_id']);
					}
				}
			}elseif($event['n4bw_save_cover'] == 'first'){
				$cover_url = $this->getPostPlaceElement($post['post_content'],'img','data-src',0);
				$cover = $this->saveRemoteImage($cover_url);
				if($cover != false)
					set_post_thumbnail($postID,$cover['attach_id']);
			}

			return true;
		}

		/**
		 * 获取采集事件
		 * @Author @快叫我韩大人
		 * @param  int  $grabID 采集事件 ID
		 * @return object       返回事件内容
		 */
		public function getGrab($grabID){
			$grabs = $this->getGrabLists();
			if($grabs == null){
				$this->wp_error->add(40006,'无法获取事件队列信息',$grabs);
				return $this->wp_error;
			}
			$ret = null;
			foreach ($grabs as $grab) {
				if($grab->ID == $grabID){
					$ret = $grab;
					break;
				}
			}
			return $ret;
		}

		public function delGrab($grabID){
			global $wpdb;
			$sql = "UPDATE {$this->n4bw_table} SET `status` = '0' WHERE `ID` = %d";
			$request = $wpdb->query($wpdb->prepare($sql,$grabID));
			wp_cache_delete('n4bw_cache_grablists');
		}

		/**
		 * 获取采集队列列表
		 * @Author @快叫我韩大人
		 * @param  string  $type 返回数据类型 OBJECT
		 * @return $type         返回所需类型的数据,不存在返回 NULL
		 */
		public function getGrabLists(){
			if(wp_cache_get('n4bw_cache_grablists'))
				return wp_cache_get('n4bw_cache_grablists');

			$sql = "SELECT * FROM {$this->n4bw_table} WHERE `status` != 0 ORDER BY `ID` DESC";
			global $wpdb;
			$request = $wpdb->get_results($sql);
			if($request == null || count($request) == 0)
				return null;

			wp_cache_set('n4bw_cache_grablists',$request);
			return $request;
		}

		/**
		 * 获取采集队列列表的 HTML
		 * @Author @快叫我韩大人
		 * @return html  输出拼接好的 html
		 */
		public function getGrabListsHtml(){
			$grabs = $this->getGrabLists();
			if($grabs == null)
				return null;

			ob_start();
		?>
			<table class="form-table n4b-listtable">
				<thead>
					<tr>
						<th>时间(执行/创建)</th>
						<th class="n4b-thead-min">类型</th>
						<th class="n4b-thead-min">状态</th>
						<th>事件内容</th>
						<th>其他</th>
						<th class="n4b-align-right">操作</th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach ($grabs as $grab) {
					$event = maybe_unserialize($grab->event);
				?>
					<tr>
						<td class="n4b-layout-tb">
							<h4>
								<?php
									if($grab->status == 3)
										echo '手动激活';
									else
										echo $grab->execution_date;
								?>
								<span>
									<?php echo $grab->create_date; ?>
								</span>
							</h4>
						</td>
						<td>
							<p><?php echo $grab->type == 1 ? '公众号' : '文章'; ?></p>
						</td>
						<td>
							<p>
							<?php 
								switch ($grab->status) {
									case 0:
										echo "已删除";
										break;
									case 1:
										echo "成功";
										break;
									case 2:
										echo "等待激活";
										break;
									default:
										echo "手动激活";
										break;
								}
							?>
							</p>
						</td>
						<td class="n4b-maskitem">
							<div class="n4b-maskbox">
								<span>图像资源:<?php echo $event['n4bw_save_image'] == 'save' ? "保存至本地" : "使用远程地址";  ?></span>
								<span>特色图像:
								<?php 
									switch ($event['n4bw_save_cover']) {
										case 'cover':
											echo "使用封面";
											break;
										case 'first':
											echo "使用第一张图片";
											break;
										default:
											echo "关闭";
											break;
									}
								?>
								</span>
								<span>视频适配:
								<?php 
									switch ($event['n4bw_save_video']) {
										case 'auto':
											echo "宽度100%,高度自适应";
											break;
										default:
											echo "使用默认设置";
											break;
									}
								?>
								</span>
								<span>作者ID:<?php echo $event['n4bw_save_author'] == 'current' ? get_current_user_id() : $event['n4bw_save_author']; ?></span>
								<span>文章状态:
								<?php 
									switch ($event['n4bw_save_poststatus']) {
										case 'publish':
											echo "已发布";
											break;
										case 'private':
											echo "私有的";
											break;
										case 'private':
											echo "私有的";
											break;
										case 'pending':
											echo "等待复审";
											break;
										case 'draft':
											echo "草稿";
											break;
										default:
											echo "定时 {$event['post_date']}";
											break;
									}
								?>
								</span>
								<span>样式重置:<?php echo $event['n4bw_save_style'] == 'reset' ? "重置" : "不重置"; ?></span>
								<span>保存分类:<?php echo $this->arrayToString($event['n4bw_save_category']); ?></span>
								<span class="n4b-line">自定义栏目</span>
								<?php if($event['n4bw_save_meta_author'] != '') echo "<span>文章作者:{$event['n4bw_save_meta_author']}</span>"; ?>
								<?php if($event['n4bw_save_meta_desc'] != '') echo "<span>文章描述:{$event['n4bw_save_meta_desc']}</span>"; ?>
								<?php if($event['n4bw_save_meta_views'] != '') echo "<span>文章阅读数:{$event['n4bw_save_meta_views']}</span>"; ?>
								<?php if($event['n4bw_save_meta_likes'] != '') echo "<span>文章点赞数:{$event['n4bw_save_meta_likes']}</span>"; ?>
									<?php if($event['n4bw_save_meta_sourceurl'] != '') echo "<span>阅读原文:{$event['n4bw_save_meta_sourceurl']}</span>"; ?>
							</div>
						</td>
						<td class="n4b-tags">
							<?php
								foreach ($event['posturls'] as $url) {
									$this->getMinUrl($url['url'],$url['status']);
								}
							?>
						</td>
						<td class="n4b-align-right">
						<form method="post" class="grab-form">
							<?php wp_nonce_field('n4bw_grab', 'n4bw_grab_field'); ?>
							<input type="hidden" name="n4bw_grabid" value="<?php esc_attr_e($grab->ID); ?>">
							<?php if($grab->status == 3){ ?>
								<input type="submit" name="start" class="button button-primary" value="启动">
							<?php } ?>
							<?php if($grab->status != 0){ ?>
								<input type="submit" name="delete" class="button" value="删除">
							<?php } ?>
						</form>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		<?php
			echo ob_get_clean();
		}

		/**
		 * 根据 URL 地址获取迷你短地址,仅为方便显示所用,短地址无实际意义!
		 * @Author @快叫我韩大人
		 * @param  string  $url    需要转为短地址的 url
		 * @param  integer $length 短地址长度
		 * @param  boolean $alink  是否输入链接
		 * @return string          输出要求的格式
		 */
		public function getMinUrl($url,$status,$length = 5,$alink = true,$e_status = true){
			$tagname = explode('/',$url);
			$tagname = substr($tagname[count($tagname) - 1],0,$length);
			$status_name = '';
			if($e_status){
				if($status == 1)
					$status_name = "(已抓取)";
				elseif($status == 0)
					$status_name = "";
			}
			if($alink)
				echo "<a href='{$url}' target='_blank'>{$tagname}{$status_name}</a>";
			else
				echo $tagname;
		}

		/**
		 * 根据文章 URL 获取相应信息
		 * @Author @快叫我韩大人
		 * @param  string  $url 单篇文章地址
		 * @return array       	返回数组数据
		 */
		public function getPostElement($url){
			$remote = wp_remote_get($url);
			if(is_wp_error($remote)){
				$this->wp_error->add(40006,$remote->get_error_message(),$url);
				return $this->wp_error;
			}
			$htmlbody = $remote['body'];

			// 页面过期
			if(strpos($htmlbody,'链接已过期') !== false){
				$this->wp_error->add(40007,'当前采集的页面已过期,请尝试重新获取地址!',$url);
				return $this->wp_error;
			}

			$html_dom = new \HtmlParser\ParserDom($htmlbody);

			$post_title = $author = $author_id = $desc = $post_content = $thumb_url = $sourceurl = $likes = $views = $copyright = '';

			$s = $html_dom->find('#activity-name',0);
			$post_title = $s->getPlainText();

			$s = $html_dom->find('.profile_nickname',0);
			$author = $s->getPlainText();

			$s = $html_dom->find('.profile_meta_value',0);
			$author_id = $s->getPlainText();

			if(preg_match_all('/var msg_desc = \"(.*?)\";/i', $htmlbody, $matches)){
				$desc = str_replace(array('&nbsp;','&amp;','\x0a'), array(' ','&',PHP_EOL), $matches[1][0]);
			}

			$s = $html_dom->find('#js_content',0);
			$post_content = $s->innerHTML();

			if(preg_match_all('/var msg_cdn_url = \"(.*?)\";/i', $htmlbody, $matches)){
				$thumb_url = str_replace('/640', '/0', $matches[1][0]);
			}

			if(preg_match_all('/var msg_source_url = \"(.*?)\";/i', $htmlbody, $matches)){
				$sourceurl = $matches[1][0];
			}

			$s = $html_dom->find('.rich_media_meta meta_original_tag',0);
			if ($s != false) {
				$copyright = $s->getPlainText();
			}

			return compact('post_title','author','author_id','desc','post_content','thumb_url','sourceurl','likes','views','copyright');
		}

		/**
		 * 清洗 HTML
		 * @Author @快叫我韩大人
		 * @param  string  $html  清洗 html 元素
		 * @param  array   $event 事件数组
		 * @return string         清洗后的内容
		 */
		public function getPostContent($htmlbody,$event){
			$html_dom = new \HtmlParser\ParserDom($this->wrapHtmlCode($htmlbody));
			// 图片清洗
			foreach ($html_dom->find('img') as $imgtag) {
				$image_src = $imgtag->getAttr('data-src');
				if($event['n4bw_save_image'] == 'save'){
					$url = $this->saveRemoteImage($image_src);
					if($url != false){
						$image_src = $url['url'];
					}
				}
				$imgtag->node->setAttribute('src',$image_src);
			}
			// 视频清洗
			foreach ($html_dom->find('.video_iframe') as $iframe) {
				$video_src = str_replace('preview.html','player.html',$iframe->getAttr('data-src'));
				if($event['n4bw_save_video'] == 'auto'){
					$ratio = $iframe->getAttr('height') / $iframe->getAttr('width');
					$iframe->node->setAttribute('width','100%');
					$iframe->node->setAttribute('height','100%');
					$iframe->node->setAttribute('data-ratio',(string)$ratio);
					$video_src = str_replace('height','height__',str_replace('width','width__',$video_src));
				}
				$iframe->node->setAttribute('src',$video_src);
			}
			return $html_dom->find('body',0)->innerHtml();
		}
		public function getPostContent_Old($htmlbody,$event){
			$html_dom = new \HtmlParser\ParserDom($this->wrapHtmlCode($htmlbody));
			$newHtml = $html_dom->find('body',0)->innerHTML();

			// 图片清洗
			foreach ($html_dom->find('img') as $imgtag) {
				$src = $imgtag->getAttr("data-src");
				$newsrc = $src;
				if($event['n4bw_save_image'] == 'save'){
					$url = $this->saveRemoteImage($src);
					if($url != false)
						$newsrc = $url['url'];
				}
				$newHtml = str_replace('data-src="'.$src.'"','src="'.$newsrc.'" '.'data-src="'.$src.'"',$newHtml);
			}

			// 视频
			foreach ($html_dom->find('.video_iframe') as $iframe) {
				$iframe->node->setAttribute('width','100%');
				print_r($iframe->outerHtml());



				// $src = $iframe->getAttr("data-src");
				// if($src != ''){
				// 	$src = str_replace('&','&amp;',$src);
				// 	$newsrc = str_replace('preview.html','player.html',$src);
				// }
				// $newHtml = str_replace('data-src="'.$src.'"','src="'.$newsrc.'" '.'data-src="'.$src.'"',$newHtml);

				// if($event['n4bw_save_video'] == 'auto'){
				// 	print_r($iframe->getAttr("width"));
				// }
				// $newHtml = str_replace('<iframe','test_iframe',$newHtml);
				// var_dump(strpos($newHtml,str_replace('&','&amp;',$iframe_code)));

			}

			return $newHtml;
		}

		// 包裹元素
		public function wrapHtmlCode($htmlbody){
			$htmlbody = "
			<!DOCTYPE html>
			<html>
				<head>
					<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
				</head>
				<body>
				{$htmlbody}
				</body>
			</html>";
			return $htmlbody;
		}

		/**
		 * 获取文章中相关元素
		 * @Author @快叫我韩大人
		 * @param  string  $html    网页代码
		 * @param  string  $tagname 要获取的元素标签名称
		 * @param  integer $place   要获取的元素位置,null表示全部元素数组
		 * @return [type]           [description]
		 */
		public function getPostPlaceElement($html,$tagname,$attr = '',$place = null){
			$html_dom = new \HtmlParser\ParserDom($this->wrapHtmlCode($htmlbody));
			$tags = $html_dom->find($tagname,$place);

			$ret = '';

			if($place){
				foreach ($tags as $tag) {
					if($attr != ''){
						$ret[] = $tag->getAttr($attr);
					}else{
						$ret[] = $tag->outerHtml();
					}
				}
			}else{
				if($attr != ''){
					$ret = $tags->getAttr($attr);
				}else{
					$ret = $tags->outerHtml();
				}
			}
			
			return $ret;
		}

		/**
		 * 保存远程图片,并写入媒体库
		 * @Author @快叫我韩大人
		 * @param  string  图片地址  需要保存至本地的图片地址
		 * @return  array           数组,返回 id,url 和 file 地址
		 */
		public function saveRemoteImage($image_src){
			$getimg = wp_remote_get($image_src);
			if($getimg['headers']['x-cache-lookup'] == 'Hit From Disktank'){
				$getimg = wp_remote_get('http://read.html5.qq.com/image?src=forum&q=5&r=0&imgflag=7&imageUrl='.$image_src);
			}
			// 验证文件
			$type = explode('/',wp_remote_retrieve_header($getimg,'content-type'));
			$typeArr = array('x-png','x-bmp','x-jpg','jpeg','gif','png','bmp');
			if(!in_array($type[1],$typeArr))
				return false;
			$type = str_replace('jpeg','jpg',str_replace('x-','',$type[1]));

			$file_name = $this->createSoleName().'.'.$type;
			$file_content = wp_remote_retrieve_body($getimg);
			$fileinfo = wp_upload_bits($file_name,null,$file_content);

			if($fileinfo['error'] == ''){
				$filetype = wp_check_filetype(basename($fileinfo['file']),null);
				$attach_id = wp_insert_attachment(array(
					'guid' => $fileinfo['url'],
					'post_mime_type' => $filetype['type'],
					'post_status' => 'inherit',
					'post_title' => $file_name
				),$fileinfo['file']);
				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata($attach_id,$fileinfo['file']);
				wp_update_attachment_metadata($attach_id,$attach_data);

				return array('attach_id' => $attach_id,'url' => $fileinfo['url'],'file' => $fileinfo['file']);
			}

			return false;
		}

		/**
		 * 根据 URL 地址获取微信文章信息
		 * @Author @快叫我韩大人
		 * @param  string  $url  需要获取的 URL 地址
		 * @return string        如果采集到内容,返回文章的 body 内容,否则返回 false 并带 wp_error
		 */
		public function remoteWechatPost($url){
			$response = wp_remote_get(esc_url($url));
			if(!is_wp_error($response))
				return $response['body'];

			$this->wp_error->add(40003,'无法采集到该地址,请检查你的地址是否规范',$url);
			return $this->wp_error;
		}


		public function updateEventUrlStatus($grabID,$url,$status){
			$grab = $this->getGrab($grabID);
			$event = maybe_unserialize($grab->event);
			for ($i = 0; $i < count($event['posturls']); $i++) {
				if(trim($event['posturls'][$i]['url']) == trim($url)){
					$event['posturls'][$i]['status'] = $status;
				}
			}

			global $wpdb;
			$request = $wpdb->update($this->n4bw_table,array('event' => maybe_serialize($event)),array('ID' => $grabID),array('%s'),array('%d'));
			if($request === 1){
				wp_cache_delete('n4bw_cache_grablists');
				return true;
			}
			return false;
		}

		public function updateGrabStatus($grabID,$status){
			$grab = $this->getGrab($grabID);
			global $wpdb;
			$request = $wpdb->update($this->n4bw_table,array('status' => $status),array('ID' => $grabID),array('%d'),array('%d'));
			if($request === 1){
				wp_cache_delete('n4bw_cache_grablists');
				return true;
			}
			return false;
		}

		/**
		 * 创建采集事件队列
		 * @Author @快叫我韩大人
		 * @param   boolean  $args 队列参数
		 * 
		 *       参数名			可空		描述             
		 *       event  		×		事件内容
		 *       create_date 	√		创建事件
		 *       execution_date √		事件激活时间
		 *       type 			×		采集类型
		 *       author 		√		事件作者
		 *        
		 * @return  boolean        使用 wp_is_error() 获取相关错误信息
		 */
		public function createGrab($args){
			$insert = array();

			/**
			 * 队列事件 不能为空
			 */
			if (!isset($args['event']) || empty($args['event']) || !is_array($args['event'])){
				$this->wp_error->add(40001,'加入至队列的事件不能为空',$args);
				return $this->wp_error;
			}

			/**
			 * 过滤事件数组中不必要出现的字段
			 * $event_element 	允许的字段
			 */
			$event_element = array(
				'posturls',
				'post_date',
				'n4bw_save_image',
				'n4bw_save_cover',
				'n4bw_save_video',
				'n4bw_save_author',
				'n4bw_save_authorid',
				'n4bw_save_poststatus',
				'n4bw_save_style',
				'n4bw_save_category',
				'n4bw_save_meta_author',
				'n4bw_save_meta_desc',
				'n4bw_save_meta_views',
				'n4bw_save_meta_likes',
				'n4bw_save_meta_sourceurl'
			);
			foreach ($args['event'] as $event_key => $event_val) {
				if(!in_array($event_key, $event_element))
					unset($args['evnet'][$event_key]);
			}
			$insert['event'] = $args['event'];

			/**
			 * 创建时间
			 */
			if (empty($args['create_date']))
				$insert['create_date'] = current_time('mysql');
			else
				$insert['create_date'] = get_date_from_gmt($args['create_date']);

			/**
			 * 激活时间
			 */
			if (empty($args['execution_date']))
				$insert['execution_date'] = current_time('mysql');
			else
				$insert['execution_date'] = get_date_from_gmt($args['execution_date']);

			if(empty($args['type']) || (int)$args['type'] <= 0){
				$this->wp_error->add(40004,'加入至队列的事件类型不能为空,1为公众号采集2为文章采集',$args);
				return $this->wp_error;
			}
			$insert['type'] = (int)$args['type'];

			/**
			 * 时间创建者,默认为当前登录用户
			 */
			$insert['author'] = get_current_user_id();
			if (!empty($args['author']) && !is_user_member_of_blog((int)$args['author']))
				$insert['author'] = $args['author'];

			/**
			 * 状态,0删除,1成功,2定时,3手动
			 */
			if(!isset($insert['status']) || (int)$insert['status'] < 0 || (int)$insert['status'] > 3)
				$insert['status'] = 3;

			global $wpdb;
			$request = $wpdb->insert($this->n4bw_table,array(
				'create_date' 	=> $insert['create_date'],
				'execution_date'=> $insert['execution_date'],
				'type'			=> $insert['type'],
				'event'			=> maybe_serialize($insert['event']),
				'author'		=> $insert['author'],
				'status'		=> $insert['status']
			),array('%s','%s','%d','%s','%s','%d'));

			if($request){
				wp_cache_delete('n4bw_cache_grablists');
				return true;
			}

			$this->wp_error->add(40002,'无法创建相关采集队列',$request);
			return $this->wp_error;
		}

		/**
		 * 检查数据表是否存在
		 * @Author @快叫我韩大人
		 * @param    boolean	$create	如果为 true ,在判断数据表不存在的情况下将自动创建
		 * @return   boolean			成功返回 true 失败返回 false
		 */
		private function checkGrabTable($create = true){
			global $wpdb;
			$wpdb->hide_errors();
			$status = $wpdb->query("SELECT * FROM `{$this->n4bw_table}`");
			if($status !== false)
				return true;

			if(!$create)
				return false;

			$this->creatGrabTable();
			return true;
			
		}
		
		/**
		 * 创建数据表
		 * @Author @快叫我韩大人
		 */
		private function creatGrabTable() {
			global $wpdb;
			$wpdb->show_errors();
			$sql_create =
			"CREATE TABLE `{$this->n4bw_table}` (
				`ID` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '队列ID',
				`create_date` datetime NOT NULL COMMENT '创建时间',
				`execution_date` datetime NOT NULL COMMENT '执行时间',
				`type` int(1) NOT NULL COMMENT '1公众号2文章',
				`event` longtext NOT NULL COMMENT '事件',
				`author` bigint(20) NOT NULL COMMENT '创建者',
				`status` int(2) DEFAULT '3' COMMENT '0删除1成功2等待3手动激活',
				PRIMARY KEY  (`ID`)
			)
			COLLATE {$wpdb->get_charset_collate()} 
			";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_create);
		}

		/**
		 * 将数组转为字符串
		 * @Author @快叫我韩大人
		 * @param  array  $array 待转换的数组
		 * @param  string  $mark 字符串分隔符
		 * @return  array        返回字符串
		 */
		private function arrayToString($array,$mark = ','){
			if(!is_array($array)){
				$this->wp_error->add(40005,'arrayToString Need Array',$array);
				return $this->wp_error;
			}
			
			$ret = '';
			for ($i = 0; $i < count($array); $i++) {
				if($i == 0){
					$ret = $array[$i];
				}else{
					$ret = $mark.$array[$i];
				}
			}
			return $ret;
		}

		/**
		 * 创建一个唯一的文件名
		 * @Author @快叫我韩大人
		 * @return string  根据时间生成的文件名,毫秒级
		 */
		private function createSoleName(){
			list($usec, $sec) = explode(" ", microtime());  
			$msec = round($usec * 1000);
			$time = date('YmdHis',time());
			$soleID = $time.$msec.rand(1000,9999).rand(1000,9999);
			$id_line = strlen($soleID);
			if($id_line != 25){
				if($id_line < 25){
					$n = 25 - $id_line;
					for ($i = 0; $i < $n; $i++) {
						$soleID .= '0';
					}
				}
			}
			return $soleID;
		}
	}
?>
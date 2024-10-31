<?php
	add_action('wp_ajax_n4b_ajax_grabform','n4b_ajax_grabform');
	function n4b_ajax_grabform(){
		if(!isset($_POST['data']) || !isset($_POST['event'])){
			echo json_encode(array('errors' => '参数错误,请刷新后再试'));
			goto end;
		}
		$data = $_POST['data'];
		$event = $_POST['event'];
		$postArr = array();
		foreach ($data as $post_data) {
			$postArr[$post_data['name']] = $post_data['value'];
		}
		$grabID = (int)$postArr['n4bw_grabid'];
		if(wp_verify_nonce($postArr['n4bw_grab_field'],'n4bw_grab')){
			$n4bwgrab = new N4bwGrab();
			$grab = $n4bwgrab->getGrab($grabID);
			if($event == 'delete' && $grab->status != 0){
				$n4bwgrab->delGrab($grabID);
		    	echo json_encode(array('refresh'));
			}elseif($grab->status == 3){
				if($grab->type == 2){
					$event = maybe_unserialize($grab->event);
					foreach ($event['posturls'] as $url) {
						if($url['status'] == 0){
							$post = $n4bwgrab->getPostElement($url['url']);
							if(is_wp_error($post)){
								$errors[] = $post->get_error_message();
								break;
							}

							$request = $n4bwgrab->createPost($event,$post);
							if(!$request){
								$errors[] = $request;
							}else{
								$n4bwgrab->updateEventUrlStatus($grabID,$url['url'],1);
							}
						}
					}
					if(count($errors) > 0){
						echo json_encode(array('errors' => $errors));
						goto end;
					}
					// 重新加载
					$grab = $n4bwgrab->getGrab($grabID);
					$event = maybe_unserialize($grab->event);
					$status = false;
					foreach ($event['posturls'] as $url) {
						if($url['status'] == 0){
							$status = true;
							break;
						}
					}
					if(!$status){
						$n4bwgrab->updateGrabStatus($grabID,1);
					}
					echo json_encode(array('refresh'));
					goto end;
				}
			}
		}
		end:;
		wp_die();
	}
?>
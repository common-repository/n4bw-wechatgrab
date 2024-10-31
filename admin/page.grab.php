<?php
	function n4bw_grab_page(){
		$n4bwgrab = new N4bwGrab();

?>
<div class="n4b-highlight-box n4b-fullpage">
	<div class="n4b-highleft">
		<h4>插件声明</h4>
		<p><code>手动采集时,请不要关闭页面!</code>该插件抓取的任何内容,都属于插件使用者个人行为,造成的任何问题与插件作者无关</p>
	</div>
</div>
<?php $n4bwgrab->getGrabListsHtml(); ?>
<?php }
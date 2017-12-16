<?php  
	include('function.php');
	if(!empty($_GET)){
		$nowTime = time();
		// ticket不存在 先获取ticket
		if(isset($_GET['media_id'])){
			$ticket = getTicket($_GET['media_id'],$_GET['type']);
			$m->query("update mp_iteminfos set ticket = '{$ticket}',qrcodeExpire = {$nowTime} where media_id = '{$_GET['media_id']}'");
		}
		// ticket存在 检测有效期  不在有效期 重新获取ticket
		if(isset($_GET['ticket'])){
			$ticket = $_GET['ticket'];
			$mr = $m->query("select media_id,qrcodeExpire from mp_iteminfos where ticket = '{$_GET['ticket']}' limit 1");
			$arr = $mr->fetch_assoc();
			// 有效期在一天前结束
			if($nowTime - $arr['qrcodeExpire'] >= 2505600){
				$ticket = getTicket($arr['media_id'],$_GET['type']);
				$m->query("update mp_iteminfos set ticket = '{$ticket}',qrcodeExpire = {$nowTime} where ticket = '{$_GET['ticket']}'");
			}
		}
		// echo $ticket;
		// 至此 已得到确切ticket
		$type = $_GET['type'] == 'news'?'图文':'视频';
		$title = iconv("utf-8","gbk",$_GET['title']."(二维码分享|{$type})");
		header("Content-Disposition:attachment;filename={$title}.jpg");
		echo getQrcode($ticket);
	}
?>
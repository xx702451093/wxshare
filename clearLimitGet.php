<?php  
	include_once("function.php");
	$config = include_once("config.php");
	$url = "https://api.weixin.qq.com/cgi-bin/clear_quota";
	$data = array(
		"appid" => $config
	);
	echo curl_send_post_to_wxapi($url,$data);
?>
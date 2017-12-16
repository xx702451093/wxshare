<?php  
	header("Content-type:text/html;Charset=utf-8;");
	$m = include('db.php');
	$config = include('config.php');
	date_default_timezone_set('PRC');
	// 日志记录
	function write_access_token_log($log){
		$log = date("Y/m/d H:i:s").$log."\r\n";
		file_put_contents('./access_token_deal.txt',$log,FILE_APPEND);
	}

	// 服务器向微信请求access_token
	function curl_get_wx_access_token($appid,$appsecret){
		// 微信access_token api
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
		 //初始化
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查  
	    //设置抓取的url
	    curl_setopt($curl, CURLOPT_URL, $url);
	    //设置头文件的信息作为数据流输出
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    //设置获取的信息以文件流的形式返回，而不是直接输出。
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    //执行命令
	    $data = curl_exec($curl);
	    //关闭URL请求
	    curl_close($curl);
	    //保存获得的数据
	    $data = json_decode($data,true);
	    // 数据检测
	    if(!empty($data['errcode'])){
			write_access_token_log('微信请求出错，错误代码'.$data['errcode']); 
			// die($data['errcode']);
		}
		// 记录日志 输出
		write_access_token_log('微信请求成功.');
		return $data;
	}

	// 得到目前可用的accessToken
	function getAccessToken(){
		global $m;
		global $config;
		$mr = $m->query('select * from mp_config limit 1');
		$saves_config = $mr->fetch_assoc();
		$nowtime = time();
		// 数据库为空 没有初始数据
		if(!is_array($saves_config)){
			$data = curl_get_wx_access_token($config['appid'],$config['appsecret']);
			//成功则写入数据库
			$add = "insert into mp_config (appid,token,appsecret,addtime,expires_in,access_token) values 
			('{$config['appid']}','{$config['token']}','{$config['appsecret']}',{$nowtime},{$data['expires_in']},'{$data['access_token']}')";
			if($m->query($add)){
				write_access_token_log('首次写入所有数据成功!');
				return $data['access_token'];
			}else{
				write_access_token_log($m->error.'首次写入失败!');
				// die('database insert failed');
			}	
		}else{
			// 有数据 分两种情况 
			// 1.距离有效期不足1分钟，此时需要重新请求
			// 2.距离有效期大于1分钟，直接取值就可以
			if($nowtime - intval($saves_config['addtime']) >= $saves_config['expires_in']-60){
				$data = curl_get_wx_access_token($saves_config['appid'],$saves_config['appsecret']);
				$save = "update mp_config set access_token = '{$data['access_token']}',addtime = {$nowtime},expires_in = {$data['expires_in']} where id = {$saves_config['id']}";
				if($m->query($save)){
					write_access_token_log('写入成功!');
					return $data['access_token'];
				}else{
					write_access_token_log($m->error.'写入失败!');
					// die('database update failed!');
				}	
			}else{
				return $saves_config['access_token'];
			}
		}
	}

	/**
	 * [curl_send_post_to_wxapi ]
	 * @param  [type] $apiUrl [微信api前半部分]
	 * @param  [type] $data   [参数]
	 * @param  string $urlext [get后缀]
	 * @return [json]         [返回值]
	 */
	function curl_send_post_to_wxapi($apiUrl,$data,$urlext=''){
		$access_token = getAccessToken();
		$url = $apiUrl."?access_token={$access_token}";
    	if(!empty($urlext)){
    		$url .= $urlext;
    	}
		 //初始化
	    $curl = curl_init();
	   	// 跳过HTTPS证书检查  
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
	    //设置抓取的url
	    curl_setopt($curl, CURLOPT_URL, $url);
	    //设置头文件的信息作为数据流输出
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    //设置获取的信息以文件流的形式返回，而不是直接输出。
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    //设置post方式提交
	    curl_setopt($curl, CURLOPT_POST, 1);
	    //设置post数据  不对中文进行转码
	    // $data = json_encode($data,JSON_UNESCAPED_UNICODE);
	    $data = json_encode($data);
	    // var_dump( $data);die;
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	    //执行命令
	    $output = curl_exec($curl);
	    //关闭URL请求
	    curl_close($curl);
	    //显示获得的数据
	    return $output;
	}

	function curl_send_get_to_wxapi($url){
		 //初始化
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查  
	    //设置抓取的url
	    curl_setopt($curl, CURLOPT_URL, $url);
	    //设置头文件的信息作为数据流输出
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    //设置获取的信息以文件流的形式返回，而不是直接输出。
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    //执行命令
	    $data = curl_exec($curl);
	    //关闭URL请求
	    curl_close($curl);
	    //保存获得的数据
	    return $data;
	}

	/**
	 * [getItemInfos ]
	 * @param  array  $post_data [请求参数]
	 * @return [json]          
	 */
	function getItemInfos($offset = 0,$type = "news"){
		$post_data = array(
					"type" => $type,
			        "offset" => $offset,
			        "count" => 20
			    );
		// 微信api
		$apiurl = "https://api.weixin.qq.com/cgi-bin/material/batchget_material";
	    ;
	    return curl_send_post_to_wxapi($apiurl,$post_data);
	}


	/**
	 * 新增素材到数据库
	 */
	function addItemToDb($currentItem){
		global $m;
		$nowTime = time();
		// media_id  媒体编号 
		// update_time  最后一次修改时间
		// content  内容 
		$currentContent = $currentItem['content'];
		// create_time 内容创建时间
		// update_time 内容最后一次修改时间
		// news_item 消息项目 有可能为同时发送的多图文消息  因此要遍历
		foreach($currentContent['news_item'] as $item){
			// title 标题
			// author 作者 
			// digest 摘要
			// content 内容
			// content_source_url 查看原文所指链接
			// thumb_media_id 缩略图id
			// show_cover_pic 是否显示封面 1为显示
			// url 内容的永久链接
			// thumb_url 缩略图链接
			// need_open_comment 是否打开评论，0不打开，1打开
			// only_fans_can_comment 是否粉丝才可评论，0所有人可评论，1粉丝才可评论
			$insert = "insert into mp_iteminfos (media_id,update_time,create_time,title,author,digest,content,content_source_url,thumb_media_id,show_cover_pic,url,thumb_url,need_open_comment,only_fans_can_comment,addTime) values ('{$currentItem['media_id']}',{$currentContent['update_time']},{$currentContent['create_time']},'{$item['title']}','{$item['author']}','{$item['digest']}','{$item['content']}','{$item['content_source_url']}','{$item['thumb_media_id']}',{$item['show_cover_pic']},'{$item['url']}','{$item['thumb_url']}',{$item['need_open_comment']},{$item['only_fans_can_comment']},{$nowTime})";
			if($m->query($insert)){
				writeDbLog('insert success , id = '.$m->insert_id);
			}else{
				writeDbLog('insert error,'.$m->error);
			}
		}
	}

	/**
	 * 新增视频素材到数据库
	 */
	function addVideoToDb($currentItem){
		global $m;
		$nowTime = time();
		// media_id  媒体编号 
		// update_time  最后一次修改时间
	    // name 标题
		// url 内容的永久链接
		$insert = "insert into mp_iteminfos (media_id,update_time,title,url,addTime,type) values ('{$currentItem['media_id']}',{$currentItem['update_time']},'{$currentItem['name']}','{$currentItem['url']}',{$nowTime},'video')";
		if($m->query($insert)){
			writeDbLog('insert success , id = '.$m->insert_id);
		}else{
			writeDbLog('insert error,'.$m->error);
		}
	}

	// 记录数据库操作日志
	function writeDbLog($log){
		$log .= "\r\n"; 
		file_put_contents('./db.log',$log,FILE_APPEND);
	}

	// 查询某消息是否被修改
	// media_id相同 修改时间不同  
	function checkIsEdit($media_id,$update_time,$type = 'news'){
		global $m;
		$select = "select count(*) from mp_iteminfos where media_id = '{$media_id}' and update_time != {$update_time} and type = '{$type}'";
		$m_r = $m->query($select);
		$arr = $m_r->fetch_row();
		if($arr[0] > 0){
			return true;
		}
		return false;	
	}

	// 查询某消息是否不存在
	// 根据media_id查询数据表 若有数据表示已存在
	function checkIsNotExists($media_id,$type = 'news'){
		global $m;
		$select = "select count(*) from mp_iteminfos where media_id = '{$media_id}' and type = '{$type}'";
		$m_r = $m->query($select);
		$arr = $m_r->fetch_row();
		if($arr[0] > 0){
			return true;
		}
		return false;	
	}

	// 根据参数获取ticket 
	// 获取临时二维码 
	function getTemporaryTicket($media_id,$type){
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create";
		$data = array(
			"expire_seconds" => 2592000,
			"action_name" => "QR_STR_SCENE",//临时二维码 参数为字符串
			"action_info" => array(
				"scene" => array(
					"scene_str" => $media_id.'&'.$type
				)
			)
		);
		// 请求ticket
		$ticket_data = curl_send_post_to_wxapi($url,$data);
		$ticket_data = json_decode($ticket_data,true);
		return urlencode($ticket_data['ticket']);
	}

	// 换取ticket
	// 获取永久二维码
	function getTicket($media_id,$type){
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create";
		$data = array(
			"action_name" => "QR_LIMIT_SCENE",//临时二维码 参数为字符串
			"action_info" => array(
				"scene" => array(
					"scene_str" => $media_id.'&'.$type
				)
			)
		);
		// 请求ticket
		$ticket_data = curl_send_post_to_wxapi($url,$data);
		$ticket_data = json_decode($ticket_data,true);
		return urlencode($ticket_data['ticket']);
	}

	// 根据ticket获取二维码
	function getQrcode($ticket){
		// 换取二维码
		$qrcodeUrl = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;
		return curl_send_get_to_wxapi($qrcodeUrl);
	}

	// 根据media_id获取标题
	function getTitleByMediaId($media_id){
		global $m;
		$mr = $m->query("select title from mp_iteminfos where media_id = '{$media_id}' limit 1");
		$arr = $mr->fetch_assoc();
		return $arr['title'];
	}
?>
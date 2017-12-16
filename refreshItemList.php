<?php  
	include('function.php');
	$mr = $m->query("select * from mp_iteminfos where type = 'news' limit 1");
	$arr = $mr->fetch_assoc(); 
	$nowTime = time();
	// 本地数据库为空  直接执行无条件查询保存
	if(!is_array($arr)){
		$itemInfo = getItemInfos();
		$jsonData = json_decode($itemInfo,true);
		// total_count 平台上的总素材数
		$requestCount = ceil($jsonData['total_count'] / 20); //需要的请求次数
		for($j=1;$j<=$requestCount;$j++){
			if($j != 1){
				$offset = ($j-1)*20;
				$itemInfo = getItemInfos($offset);
				$jsonData = json_decode($itemInfo,true);
			}
			// item_count 当前返回的素材数
			// item 当前返回的项目  数组
			for($i=0;$i<$jsonData['item_count'];$i++){
				// 每一个素材
				$currentItem = $jsonData['item'][$i];
				addItemToDb($currentItem);
			}
			// 更新最后一次操作时间
			$mr_param = $m->query("select * from mp_param limit 1");
			$param = $mr_param->fetch_row();
			if(is_array($param)){
				$m->query("update mp_param set update_time = {$nowTime}");
			}else{
				$m->query("insert into mp_param (update_time) values ({$nowTime})");
			}
			// 销毁变量
			unset($offset);
			unset($itemInfo);
			unset($jsonData);
		}
	}else{
		// 查询最后一次操作时间
		$mr_param = $m->query("select update_time from mp_param limit 1");
		$param = $mr_param->fetch_assoc();
		// 判断查询时间间隔  300s内只能操作一次  限制查询次数  
		if($nowTime - $param['update_time'] < 120){
			die('2分钟内只能操作一次！下次可操作时间为'.date('Y-m-d H:i:s',$param['update_time']+120));
		}else{
			// 更新最后一次操作时间
			$m->query("update mp_param set update_time = {$nowTime}"); 
			// 时间范围允许
			$itemInfo = getItemInfos();
			// var_dump($itemInfo);die;
			$jsonData = json_decode($itemInfo,true);
			// 查看每个素材的media_id和最后修改时间  如果相同信息已存在  则不记录
			for($i=0;$i<$jsonData['item_count'];$i++){
				// 每一个素材
				$currentItem = $jsonData['item'][$i];
				// media_id  媒体编号 
				// update_time  最后一次修改时间
				
				// 如果该消息已存在
				if(checkIsNotExists($currentItem['media_id'])){
					// action 1 如果该消息被修改 则记录最新消息  由于会存在多图文消息的media_id相同的情况  因此不采取更新的做法 
					if(checkIsEdit($currentItem['media_id'],$currentItem['update_time'])){
						$select = "select id from mp_iteminfos where media_id = '{$currentItem['media_id']}' and update_time != {$currentItem['update_time']} and type = 'news'";
						$m_r = $m->query($select);
						while($idArr = $m_r->fetch_assoc()){
							$result	= $m->query("delete from mp_iteminfos where id = {$idArr['id']}");
							if($result){
								writeDbLog('第'.$idArr['id'].'条被修改，已被删除！');
							}else{
								writeDbLog('第'.$idArr['id'].'条被修改，删除失败！');
							}
						}
						addItemToDb($currentItem);	
					}
				}else{
					// action 2 不存在该消息
					addItemToDb($currentItem);
				}
			}
		}
	}
	// 销毁变量
	unset($arr);
	unset($mr);
	$mr = $m->query("select * from mp_iteminfos where type = 'video' limit 1");
	$arr = $mr->fetch_assoc(); 
	// 本地数据库为空  直接执行无条件查询保存
	if(!is_array($arr)){
		$itemInfo = getItemInfos(0,"video");
		$jsonData = json_decode($itemInfo,true);
		// total_count 平台上的总视频数
		$requestCount = ceil($jsonData['total_count'] / 20); //需要的请求次数
		for($j=1;$j<=$requestCount;$j++){
			if($j != 1){
				$offset = ($j-1)*20;
				$itemInfo = getItemInfos($offset,"video");
				$jsonData = json_decode($itemInfo,true);
			}
			// item_count 当前返回的素材数
			// item 当前返回的项目  数组
			for($i=0;$i<$jsonData['item_count'];$i++){
				// 每一个素材
				$currentItem = $jsonData['item'][$i];
				addVideoToDb($currentItem);
			}
			// 更新最后一次操作时间
			$mr_param = $m->query("select * from mp_param limit 1");
			$param = $mr_param->fetch_row();
			if(is_array($param)){
				$m->query("update mp_param set video_update_time = {$nowTime}");
			}else{
				$m->query("insert into mp_param (video_update_time) values ({$nowTime})");
			}
			// 销毁变量
			unset($offset);
			unset($itemInfo);
			unset($jsonData);
		}
	}else{
		// 查询最后一次操作时间
		$mr_param = $m->query("select video_update_time from mp_param limit 1");
		$param = $mr_param->fetch_assoc();
		// 判断查询时间间隔  300s内只能操作一次  限制查询次数  
		if($nowTime - $param['video_update_time'] < 120){
			die('2分钟内只能操作一次！下次可操作时间为'.date('Y-m-d H:i:s',$param['video_update_time']+120));
		}else{
			// 更新最后一次操作时间
			$m->query("update mp_param set video_update_time = {$nowTime}"); 
			// 时间范围允许
			$itemInfo = getItemInfos(0,"video");
			$jsonData = json_decode($itemInfo,true);
			// 查看每个素材的media_id和最后修改时间  如果相同信息已存在  则不记录
			for($i=0;$i<$jsonData['item_count'];$i++){
				// 每一个素材
				$currentItem = $jsonData['item'][$i];
				// media_id  媒体编号 
				// update_time  最后一次修改时间

				// 如果该消息已存在
				if(checkIsNotExists($currentItem['media_id'],'video')){
					// action 1 如果该消息被修改 则记录最新消息  由于会存在多图文消息的media_id相同的情况  因此不采取更新的做法 
					if(checkIsEdit($currentItem['media_id'],$currentItem['update_time'],'video')){
						$select = "select id from mp_iteminfos where media_id = '{$currentItem['media_id']}' and update_time != {$currentItem['update_time']} and type = 'video'";
						$m_r = $m->query($select);
						while($idArr = $m_r->fetch_assoc()){
							$result	= $m->query("delete from mp_iteminfos where id = {$idArr['id']}");
							if($result){
								writeDbLog('第'.$idArr['id'].'条被修改，已被删除！');
							}else{
								writeDbLog('第'.$idArr['id'].'条被修改，删除失败！');
							}
						}
						addVideoToDb($currentItem);	
					}
				}else{
					// action 2 不存在该消息
					addVideoToDb($currentItem);
				}
			}
		}
	}

	echo 'success';
?>
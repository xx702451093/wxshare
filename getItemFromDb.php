<?php  
	header("Content-type:text/html;Charset=utf-8;");
	$m = include_once('db.php');
	$arrs = array();
	$mr = $m->query('select url,title,media_id,ticket,type from mp_iteminfos where 1 order by update_time desc');
	while($arr = $mr->fetch_assoc()){
		$arrs[] = $arr;
	}
	echo json_encode($arrs);
?>
<?php  
	$config = include_once("config.php");
	// do connect
	$m = new mysqli($config['dbhostandport'],$config['dbuser'],$config['dbpass'],$config['dbname']);
	$m->set_charset("utf8");
	if(!$m){
		die($m->error.',database connect failed！');
	}
	return $m;
<?php  
	$m = include("db.php");
	// 改变状态
	if(isset($_GET['deal']) && $_GET['deal'] == 'status'){
		$id = $_GET['id'];
		$m->query('update mp_keywords set status = !status where id ='.$id);
		header("location:keywordsBack.php");
	}
	// 删除
	if(isset($_GET['deal']) && $_GET['deal'] == 'delete'){
		$id = $_GET['id'];
		$m->query('delete from mp_keywords where id ='.$id);
		header("location:keywordsBack.php");
	}
	// 查询列表
	$mr1 = $m->query("select count(id) from mp_keywords");
	$count1 = $mr1->fetch_row();
	if($count1[0] > 0){
		$mr2 = $m->query("select k.id,keyword,k.media_id,url,title,status from mp_keywords k inner join mp_iteminfos i on i.media_id = k.media_id order by k.id asc");
	}
	// 查询图文消息列表
	$mr3 = $m->query('select count(id) from mp_iteminfos');
	$count2 = $mr3->fetch_row();
	if($count2[0] > 0){
		$mr4 = $m->query('select title,media_id from mp_iteminfos order by update_time desc');
	}
	// 有提交
	if(!empty($_POST)){
		$error = array(
			'code' => 0,
			'msg' => '操作成功！即将跳转！'
		);         
		$keyword = trim($_POST['keyword']);
		$media_id = $_POST['media_id'];
		if(empty($keyword)){
			$error['code'] = 'empty';
			$error['msg'] = '关键字必须填写！';
		}elseif(empty($media_id)){
			$error['code'] = 'empty';
			$error['msg'] = '请正确选择图文消息，如果没有图文消息，请刷新图文消息列表。';
		}else{
			$media_id = trim($_POST['media_id']);
			$mr5 = $m->query("select id from mp_keywords where keyword = '{$keyword}' limit 1");
			$arr = $mr5->fetch_assoc();
			if(is_array($arr)){
				$error['code'] = 'exist';
				$error['msg'] = '已经存在该关键字信息，编号为'.$arr['id'].'，可直接修改。';
			}else{
				$ri = $m->query("insert into mp_keywords (keyword,media_id) values ('{$keyword}','{$media_id}')");
				if(!$ri){
					$error['code'] = 'insert';
					$error['msg'] = '新增关键词回复失败！失败原因'.$m->error;
				}
			}	
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	<style>
		*{
			margin:0;
			padding:0;
		}
		.showWords{
			display: block;
		}
		.showError{
			color: red;
		}
		.showSuccess{
			color:green;
		}
	</style>
</head>
<body>
	<h2>关键字回复列表</h2>
	<?php  
		if(intval($count1[0]) === 0){
			echo '目前还没有关键字回复消息~请添加~';
		}else{
	?>
	<table border cellspacing="0">
		<thead>	
			<tr>
				<th>编号</th>
				<th>关键字</th>
				<th>回复图文消息(点击标题预览)</th>
				<th>状态</th>
				<th>操作</th>
			</tr>
		</thead>
		<tbody>
		<?php 
			while($keyItem = $mr2->fetch_assoc()){
		?>
			<tr>
				<td><?php echo $keyItem['id']; ?></td>
				<td><?php echo $keyItem['keyword']; ?></td>
				<td>
					<a href="<?php echo $keyItem['url']; ?>" target="_blank"><?php echo $keyItem['title']; ?></a>
				</td>
				<td><?php echo $keyItem['status'] == 1?'已启用':'禁用中'; ?></td>
				<td>
					<!-- <a href="#">编辑</a>| -->
					<a href="?deal=delete&id=<?php echo $keyItem['id']; ?>" onclick="return confirm('您确定要删除吗？该操作不能撤回！')">删除</a>|
					<a href="?deal=status&id=<?php echo $keyItem['id']; ?>"><?php echo $keyItem['status'] == 1?'禁用':'启用'; ?></a>
				</td>
			</tr>		
		<?php
			}
		?>
		</tbody>
	</table>
	<?php		
		}
	?>
	<?php  
		if(isset($error)){
			// 成功输出
			if($error['code'] === 0){
	?>
				<span class="showWords showSuccess"><?php echo $error['msg']; ?></span>
	<?php
				header('refresh:1;url=keywordsBack.php');
			}else{//失败输出
	?>
				<span class="showWords showError"><?php echo $error['msg']; ?></span>
	<?php			
			}	
		}
	?>
	<h2>添加关键字回复</h2>
	<form action="" method="post">
		请输入关键字<input type="text" name="keyword" required>
		<br>
		<?php  
			if(intval($count2[0]) === 0){
		?>
		<h5>当前没有图文消息，请尝试刷新.</h5>	
		<?php
			}else{
		?>
		请选择图文消息
		<select name="media_id">
			<option value="0">请选择图文消息</option>
		<?php  
				while($optionItem = $mr4->fetch_assoc()){
		?>
			<option value="<?php echo $optionItem['media_id'] ?>"><?php echo $optionItem['title'] ?></option>
		<?php
				}
			}
		?>
		</select>
		<input type="submit"><br>
		<a onclick="return confirm('您确定要刷新列表吗？该操作两分钟只能进行一次。');" href="javascript:refreshItemLists()">点击刷新图文消息列表</a>
	</form>
	<script src="../jquery-1.11.3.js"></script>
	<script>
		function refreshItemLists(){
			var d = new Date();
			$.get(
				'refreshItemList.php?date='+d.getTime(),
				'',
				function(msg){
					if(msg == 'success'){
						alert('刷新成功！');
						location.reload();
					}else{
						alert(msg);
					}
				}
			);
		}
	</script>
</body>
</html>
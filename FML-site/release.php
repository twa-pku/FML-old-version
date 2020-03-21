<?php
if(isset($_COOKIE['username']) && $_COOKIE['username']==md5('root')){
$conn=mysqli_connect("localhost","admin","","fml",'3306','/var/lib/mysql/mysql.sock');
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
$team=mysqli_real_escape_string($conn,strtoupper($_GET['team']));
$player=mysqli_real_escape_string($conn,$_GET['player']);

//判断是否能解约
if(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Name='".$player."' AND Team='".$team."'"))==0){
	echo($player."不在".$team."!");
}
//能则更新数据库并写日志
else{
	mysqli_query($conn,"UPDATE current SET Team=NULL,Price=NULL WHERE Name='".$player."'");
	$file=fopen("logs.txt", "a");
	fwrite($file,$player." in ".$team." released at ".date('Y-m-d H:i:s',time()+8*3600)."\n");
	fclose($file);
	echo("操作成功");
}
mysqli_close($conn);
}
else{
	echo("没有权限");
}
?>

<?php
if(isset($_COOKIE['username']) && $_COOKIE['username']=='root'){
$team=strtoupper($_GET['team']);
$player=$_GET['player'];
$money=$_GET['money'];

$conn=mysqli_connect("localhost","admin","","fml",'3306','/var/lib/mysql/mysql.sock');
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
//自由签不合规的条件列举
if(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM teams WHERE Abbr='".$team."'"))==0){
	echo("球队输入错误。");
}
elseif(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Name='".$player."'"))==0){
	echo("查无此人");
}
elseif(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Name='".$player."' AND Team=''"))==0){
	echo("该球员已有主。");
}
elseif (mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Team='".$team."'"))>=22) {
 	echo($team."已满22人！");
}
elseif (mysqli_num_rows(mysqli_query($conn,"SELECT * FROM teams WHERE Abbr='".$team."' AND Money>=".$money))==0) {
 	echo($team."没有足够的资金！");
}
elseif(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Name='".$player."' AND OwnerNum<3"))==0){
	echo($player."已经被签约三次！");
}
elseif(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Name='".$player."' AND (Owner1='".$team."' OR Owner2='".$team."' OR Owner3='".$team."')"))>0){
	echo($player."已经被".$team."签约过！");
}
//否则，自由签有效，更新数据库
else{
	mysqli_query($conn,"UPDATE current SET Team='".$team."',Price=".$money.",OwnerNum=(SELECT OwnerNum FROM current WHERE Name='".$player."')+1 WHERE Name='".$player."'");
	mysqli_query($conn,"UPDATE teams SET Money=(SELECT Money FROM teams WHERE Abbr='".$team."')-".$money." WHERE Abbr='".$team."'");//调整money
	$res=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Owner1,Owner2,Owner3 FROM current WHERE Name='".$player."'"));
	if($res['Owner1']==""){
		mysqli_query($conn,"UPDATE current SET Owner1='".$team."' WHERE Name='".$player."'");
	}
	elseif($res['Owner2']==""){
		mysqli_query($conn,"UPDATE current SET Owner2='".$team."' WHERE Name='".$player."'");
	}
	elseif($res['Owner3']==""){
		mysqli_query($conn,"UPDATE current SET Owner3='".$team."' WHERE Name='".$player."'");
	}
	//在日志中记录签约
	$file=fopen("logs.txt", "a");
	fwrite($file,$team." sign ".$player." at ".date('Y-m-d H:i:s',time()+8*3600)."\n");
	fclose($file);
	echo($player."已加入".$team."。");
}
mysqli_close($conn);
}
else{
	echo("没有权限");
}
?>

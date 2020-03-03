<?php
if(isset($_COOKIE['username']) && $_COOKIE['username']=='root'){
$str=$_GET["str"];

//整个文档完全就是把submitscoredPlayer.php做的事反过来做一遍，结构相同
$conn=mysqli_connect("localhost","admin","","fml",'3306','/var/lib/mysql/mysql.sock');
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
$res=mysqli_query($conn,"SELECT tmpGoal,Team FROM current WHERE Name='".$str."'");
if(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM teams WHERE tmpCode=1"))==0){
	echo("现在不是比赛时间！");
}
elseif(mysqli_num_rows($res)==0){
	echo("查无此人！");
}
elseif(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Name='".$str."' AND Team IS NULL"))>0){
	echo("此人无主。");
}
elseif(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM current WHERE Name='".$str."' AND tmpGoal>0"))==0){
	echo("本轮未提交此人进球。");
}
else{
	$team=mysqli_fetch_assoc($res)['Team'];
	mysqli_query($conn,"UPDATE current SET tmpGoal=(SELECT tmpGoal FROM current WHERE Name='".$str."')-1 WHERE Name='".$str."'");
	$lineup=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Lineup FROM teams WHERE Abbr='".$team."'"))['Lineup'];
	$array=explode(' ',str_replace('\"','',str_replace("/", " ", strtolower($lineup))));
	$file=fopen("logs.txt", "a");
	$resultf=mysqli_fetch_assoc(mysqli_query($conn,"SELECT tmpGoal,tmpresGoal,tmpCode,Win,Draw,Lose,Points,resWin,resDraw,resLose,resPoints,Goalfor,resGoalfor FROM teams WHERE Abbr='".$team."'"));
	$tmpCode=$resultf['tmpCode'];
	if($tmpCode%2==0)
		$resulta=mysqli_fetch_assoc(mysqli_query($conn,"SELECT tmpGoal,tmpresGoal,Win,Draw,Lose,Points,resWin,resDraw,resLose,resPoints,Goalagainst,resGoalagainst,Abbr FROM teams WHERE tmpCode=".($tmpCode-1)));
	else
		$resulta=mysqli_fetch_assoc(mysqli_query($conn,"SELECT tmpGoal,tmpresGoal,Win,Draw,Lose,Points,resWin,resDraw,resLose,resPoints,Goalagainst,resGoalagainst,Abbr FROM teams WHERE tmpCode=".($tmpCode+1)));
	$team2=$resulta['Abbr'];
	if(in_array(strtolower($str), $array)){
		if($resultf['tmpGoal']==$resulta['tmpGoal']+1){
			mysqli_query($conn,"UPDATE teams SET Win=".($resultf['Win']-1).",Draw=".($resultf['Draw']+1).",Points=".($resultf['Points']-2).",tmpGoal=".($resultf['tmpGoal']-1).",Goalfor=".($resultf['Goalfor']-1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET Lose=".($resulta['Lose']-1).",Draw=".($resulta['Draw']+1).",Points=".($resulta['Points']+1).",Goalagainst=".($resulta['Goalagainst']-1)." WHERE Abbr='".$team2."'");
		}
		elseif($resultf['tmpGoal']==$resulta['tmpGoal']){
			mysqli_query($conn,"UPDATE teams SET Lose=".($resultf['Lose']+1).",Draw=".($resultf['Draw']-1).",Points=".($resultf['Points']-1).",tmpGoal=".($resultf['tmpGoal']-1).",Goalfor=".($resultf['Goalfor']-1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET Win=".($resulta['Win']+1).",Draw=".($resulta['Draw']-1).",Points=".($resulta['Points']+2).",Goalagainst=".($resulta['Goalagainst']-1)." WHERE Abbr='".$team2."'");
		}
		else{
			mysqli_query($conn,"UPDATE teams SET tmpGoal=".($resultf['tmpGoal']-1).",Goalfor=".($resultf['Goalfor']-1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET Goalagainst=".($resulta['Goalagainst']-1)." WHERE Abbr='".$team2."'");
		}
	}
	else{
		if($resultf['tmpresGoal']==$resulta['tmpresGoal']+1){
			mysqli_query($conn,"UPDATE teams SET resWin=".($resultf['resWin']-1).",resDraw=".($resultf['resDraw']+1).",resPoints=".($resultf['resPoints']-2).",tmpresGoal=".($resultf['tmpresGoal']-1).",resGoalfor=".($resultf['resGoalfor']-1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET resLose=".($resulta['resLose']-1).",resDraw=".($resulta['resDraw']+1).",resPoints=".($resulta['resPoints']+1).",resGoalagainst=".($resulta['resGoalagainst']-1)." WHERE Abbr='".$team2."'");
		}
		elseif($resultf['tmpresGoal']==$resulta['tmpresGoal']){
			mysqli_query($conn,"UPDATE teams SET resLose=".($resultf['resLose']+1).",resDraw=".($resultf['resDraw']-1).",resPoints=".($resultf['resPoints']-1).",tmpresGoal=".($resultf['tmpresGoal']-1).",resGoalfor=".($resultf['resGoalfor']-1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET resWin=".($resulta['resWin']+1).",resDraw=".($resulta['resDraw']-1).",resPoints=".($resulta['resPoints']+2).",resGoalagainst=".($resulta['resGoalagainst']-1)." WHERE Abbr='".$team2."'");
		}
		else{
			mysqli_query($conn,"UPDATE teams SET tmpresGoal=".($resultf['tmpresGoal']-1).",resGoalfor=".($resultf['resGoalfor']-1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET resGoalagainst=".($resulta['resGoalagainst']-1)." WHERE Abbr='".$team2."'");
		}
	}
	fwrite($file,"Delete ".$str."'s goal at ".date('Y-m-d H:i:s',time()+8*3600)."\n");
	echo("已撤销".$str."的进球");
}
mysqli_close($conn);
}
else{
	echo("没有权限");
}
?>

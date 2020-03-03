<?php
$str=$_GET["str"];

$conn=mysqli_connect("localhost","admin","","fml",'3306','/var/lib/mysql/mysql.sock');//未来可能得设置一个新的权限级别，假如开放玩家提交进球
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
//判断一下条件
$statusquery=mysqli_fetch_assoc(mysqli_query($conn,"SELECT LAST_SCORED_PLAYER,LAST_MODIFIED FROM status WHERE Activity='FML'"));
if($statusquery['LAST_SCORED_PLAYER']==$str && time()-$statusquery['LAST_MODIFIED']<=300){
	echo("请检查是否有其他人已提交过该进球。");
	return;
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
//没问题，则开始更新数据库
/*要更新的字段：
teams:
胜/平/负，积分，本轮进球
current:
本轮进球
*/
else{
	$team=mysqli_fetch_assoc($res)['Team'];
	mysqli_query($conn,"UPDATE current SET tmpGoal=1+(SELECT tmpGoal FROM current WHERE Name='".$str."') WHERE Name='".$str."'");
	$lineup=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Lineup FROM teams WHERE Abbr='".$team."'"))['Lineup'];
	$array=explode(' ',str_replace("\"","",str_replace("/", " ", strtolower($lineup))));
	mysqli_query($conn,"UPDATE status SET LAST_MODIFIED=".time().",LAST_SCORED_PLAYER='".$str."' WHERE Activity='FML'");
	$resultf=mysqli_fetch_assoc(mysqli_query($conn,"SELECT tmpGoal,tmpresGoal,tmpCode,Win,Draw,Lose,Points,resWin,resDraw,resLose,resPoints,Goalfor,resGoalfor FROM teams WHERE Abbr='".$team."'"));
	//通过tmpCode找到本轮对手
	$tmpCode=$resultf['tmpCode'];
	if($tmpCode%2==0)
		$resulta=mysqli_fetch_assoc(mysqli_query($conn,"SELECT tmpGoal,tmpresGoal,Win,Draw,Lose,Points,resWin,resDraw,resLose,resPoints,Goalagainst,resGoalagainst,Abbr FROM teams WHERE tmpCode=".($tmpCode-1)));
	else
		$resulta=mysqli_fetch_assoc(mysqli_query($conn,"SELECT tmpGoal,tmpresGoal,Win,Draw,Lose,Points,resWin,resDraw,resLose,resPoints,Goalagainst,resGoalagainst,Abbr FROM teams WHERE tmpCode=".($tmpCode+1)));
	$file=fopen("logs.txt", "a");
	$team2=$resulta['Abbr'];
	if(in_array(strtolower($str), $array)){//一线队或预备队
		//判断比赛结果
		//除了之前进球差为0或-1外，其余情况不会导致胜负发生变化
		if($resultf['tmpGoal']==$resulta['tmpGoal']){
			mysqli_query($conn,"UPDATE teams SET Win=".($resultf['Win']+1).",Draw=".($resultf['Draw']-1).",Points=".($resultf['Points']+2).",tmpGoal=".($resultf['tmpGoal']+1).",Goalfor=".($resultf['Goalfor']+1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET Lose=".($resulta['Lose']+1).",Draw=".($resulta['Draw']-1).",Points=".($resulta['Points']-1).",Goalagainst=".($resulta['Goalagainst']+1)." WHERE Abbr='".$team2."'");
		}
		elseif($resultf['tmpGoal']+1==$resulta['tmpGoal']){
			mysqli_query($conn,"UPDATE teams SET Lose=".($resultf['Lose']-1).",Draw=".($resultf['Draw']+1).",Points=".($resultf['Points']+1).",tmpGoal=".($resultf['tmpGoal']+1).",Goalfor=".($resultf['Goalfor']+1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET Win=".($resulta['Win']-1).",Draw=".($resulta['Draw']+1).",Points=".($resulta['Points']-2).",Goalagainst=".($resulta['Goalagainst']+1)." WHERE Abbr='".$team2."'");
		}
		else{
			mysqli_query($conn,"UPDATE teams SET tmpGoal=".($resultf['tmpGoal']+1).",Goalfor=".($resultf['Goalfor']+1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET Goalagainst=".($resulta['Goalagainst']+1)." WHERE Abbr='".$team2."'");
		}
		fwrite($file,"Add ".$str."'s goal to ".$team." at ".date('Y-m-d H:i:s',time()+8*3600)."\n");
		echo("已添加".$str."到".$team);
	}
	else{
		if($resultf['tmpresGoal']==$resulta['tmpresGoal']){
			mysqli_query($conn,"UPDATE teams SET resWin=".($resultf['resWin']+1).",resDraw=".($resultf['resDraw']-1).",resPoints=".($resultf['resPoints']+2).",tmpresGoal=".($resultf['tmpresGoal']+1).",resGoalfor=".($resultf['resGoalfor']+1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET resLose=".($resulta['resLose']+1).",resDraw=".($resulta['resDraw']-1).",resPoints=".($resulta['resPoints']-1).",resGoalagainst=".($resulta['resGoalagainst']+1)." WHERE Abbr='".$team2."'");
		}
		elseif($resultf['tmpresGoal']+1==$resulta['tmpresGoal']){
			mysqli_query($conn,"UPDATE teams SET resLose=".($resultf['resLose']-1).",resDraw=".($resultf['resDraw']+1).",resPoints=".($resultf['resPoints']+1).",tmpresGoal=".($resultf['tmpresGoal']+1).",resGoalfor=".($resultf['resGoalfor']+1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET resWin=".($resulta['resWin']-1).",resDraw=".($resulta['resDraw']+1).",resPoints=".($resulta['resPoints']-2).",resGoalagainst=".($resulta['resGoalagainst']+1)." WHERE Abbr='".$team2."'");
		}
		else{
			mysqli_query($conn,"UPDATE teams SET tmpresGoal=".($resultf['tmpresGoal']+1).",resGoalfor=".($resultf['resGoalfor']+1)." WHERE Abbr='".$team."'");
			mysqli_query($conn,"UPDATE teams SET resGoalagainst=".($resulta['resGoalagainst']+1)." WHERE Abbr='".$team2."'");
		}
		fwrite($file,"Add ".$str."'s goal to ".strtolower($team)." at ".date('Y-m-d H:i:s',time()+8*3600)."\n");
		echo("已添加".$str."到".strtolower($team));
	}
	fclose($file);
}
mysqli_close($conn);
?>

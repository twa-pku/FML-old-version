<?php
if(isset($_COOKIE['username']) && $_COOKIE['username']=='root'){
$conn=mysqli_connect("localhost","admin","","fml",'3306','/var/lib/mysql/mysql.sock');
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
//检查是否正在进行该轮
$match_on=mysqli_fetch_assoc(mysqli_query($conn,"SELECT MATCH_ON FROM status WHERE Activity='FML'"))['MATCH_ON'];
if($match_on==0){
	echo("<script>alert('没有比赛正在进行！');window.close();</script>");
	return;
}

/*要做的事情：
teams：
将之前的排名保存到prerank。
将所有球队的一线队进球和其对手进球比较，得到临时积分和结果。		更新直播帖，并另存为文件。
更新积分，轮次，战绩字符串。将积分排序，得到排名。			更新积分榜并另存为文件。
给每个球队发钱。
将临时进球，预备队进球，tmpcode，临时积分清零。
current:
更新每个球员的一线队/预备队进球。		更新射手榜，并另存为文件。
将临时进球清零。
status:
结束这一轮。
*/

//首先更新teams数据库中除了最新排名外的其它元素，并输出直播帖保存。一线队和预备队格式相同
ob_start();
$round=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Round FROM teams WHERE Rank=1"))['Round']+1;
echo("<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<title>第".$round."轮双线直播帖&首发阵容</title>
</head>
<body>
	<div>一线队</div>");
	for($i=0;$i<8;$i++){
		//输出一线队直播帖。具体细节和直播帖文件相同。
		echo("<div>");
		$res1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Rank,tmpGoal,Lineup,Money,Goalfor,Goalagainst,Round,Win,Draw,Lose,Points,charResult FROM teams WHERE tmpCode=".(1+2*$i)));
		$res2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Rank,tmpGoal,Lineup,Money,Goalfor,Goalagainst,Round,Win,Draw,Lose,Points,charResult FROM teams WHERE tmpCode=".(2+2*$i)));
		$team1=$res1['Abbr'];
		echo($res1['tmpGoal']);
		echo("&nbsp;");
		echo($team1);
		echo("(");
		$rank1=$res1['Rank'];
		if($rank1<10)
			echo("0");
		echo($rank1);
		echo(")");
		echo("&nbsp;&nbsp;&nbsp;&nbsp;");
		$array1=explode(" ", str_replace("\"","",str_replace("/", " ", $res1['Lineup'])));
		$players1=mysqli_query($conn,"SELECT tmpGoal,Name FROM current WHERE Team='".$team1."' AND tmpGoal>0");
		while($row1=mysqli_fetch_assoc($players1)){
			if(in_array($row1['Name'], $array1)){
				echo("&nbsp;".$row1['Name']);
				if($row1['tmpGoal']>1){
					echo("*".$row1['tmpGoal']);
				}
			}
		}
		echo("</div><div>");
		$team2=$res2['Abbr'];
		echo($res2['tmpGoal']);
		echo("&nbsp;");
		echo($team2);
		echo("(");
		$rank2=$res2['Rank'];
		if($rank2<10)
			echo("0");
		echo($rank2);
		echo(")");
		echo("&nbsp;&nbsp;&nbsp;&nbsp;");
		$array2=explode(" ", str_replace("\"","",str_replace("/", " ", $res2['Lineup'])));
		$players2=mysqli_query($conn,"SELECT tmpGoal,Name FROM current WHERE Team='".$team2."' AND tmpGoal>0");
		while($row2=mysqli_fetch_assoc($players2)){
			if(in_array($row2['Name'], $array2)){
				echo("&nbsp;".$row2['Name']);
				if($row2['tmpGoal']>1){
					echo("*".$row2['tmpGoal']);
				}
			}
		}
		echo("</div><p></p>");
		//上面是输出直播帖，下面开始更新数据库
		//根据结果更新近期结果字符串
		if($res1['tmpGoal']>$res2['tmpGoal']){
			$charres1=$res1['charResult']."W";
			$charres2=$res2['charResult']."L";
		}
		elseif($res1['tmpGoal']<$res2['tmpGoal']){
			$charres1=$res1['charResult']."L";
			$charres2=$res2['charResult']."W";
		}
		else{
			$charres1=$res1['charResult']."D";
			$charres2=$res2['charResult']."D";
		}
		//更新轮次，进球丢球，积分，此前排名及钱
		mysqli_query($conn,"UPDATE teams SET Round=".$round.",Money=".$res1['Money']."+(".$res2['tmpGoal']."*5),preRank=".$res1['Rank'].",charResult='".$charres1."' WHERE Abbr='".$team1."'");
		mysqli_query($conn,"UPDATE teams SET Round=".$round.",Money=".$res2['Money']."+(".$res1['tmpGoal']."*5),preRank=".$res2['Rank'].",charResult='".$charres2."' WHERE Abbr='".$team2."'");
	}
	echo("首发阵容");
	for($i=0;$i<8;$i++){
		$res1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Lineup FROM teams WHERE tmpCode=".(1+2*$i)));
		$res2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Lineup FROM teams WHERE tmpCode=".(2+2*$i)));
		echo("<div>");
		echo($res1['Abbr']);
		echo(" ");
		echo(str_replace("\"","",$res1['Lineup']));
		echo("</div>");
		echo("<div>");
		echo($res2['Abbr']);
		echo(" ");
		echo(str_replace("\"","",$res2['Lineup']));
		echo("</div><p></p>");
	}
	echo("预备队");
	for($i=0;$i<8;$i++){
		echo("<div>");
		$res1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,resRank,tmpresGoal,Lineup,resGoalfor,resGoalagainst,resWin,resDraw,resLose,resPoints,rescharResult FROM teams WHERE tmpCode=".(1+2*$i)));
		$res2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,resRank,tmpresGoal,Lineup,resGoalfor,resGoalagainst,resWin,resDraw,resLose,resPoints,rescharResult FROM teams WHERE tmpCode=".(2+2*$i)));
		$team1=$res1['Abbr'];
		echo($res1['tmpresGoal']);
		echo("&nbsp;");
		echo(strtolower($team1));
		echo("(");
		$rank1=$res1['resRank'];
		if($rank1<10)
			echo("0");
		echo($rank1);
		echo(")");
		echo("&nbsp;&nbsp;&nbsp;&nbsp;");
		$array1=explode(" ", str_replace("\"","",str_replace("/", " ", $res1['Lineup'])));
		$players1=mysqli_query($conn,"SELECT tmpGoal,Name FROM current WHERE Team='".$team1."' AND tmpGoal>0");
		while($row1=mysqli_fetch_assoc($players1)){
			if(!in_array($row1['Name'], $array1)){
				echo("&nbsp;".$row1['Name']);
				if($row1['tmpGoal']>1){
					echo("*".$row1['tmpGoal']);
				}
			}
		}
		echo("</div><div>");
		$team2=$res2['Abbr'];
		echo($res2['tmpresGoal']);
		echo("&nbsp;");
		echo(strtolower($team2));
		echo("(");
		$rank2=$res2['resRank'];
		if($rank2<10)
			echo("0");
		echo($rank2);
		echo(")");
		echo("&nbsp;&nbsp;&nbsp;&nbsp;");
		$array2=explode(" ", str_replace("\"","",str_replace("/", " ", $res2['Lineup'])));
		$players2=mysqli_query($conn,"SELECT tmpGoal,Name FROM current WHERE Team='".$team2."' AND tmpGoal>0");
		while($row2=mysqli_fetch_assoc($players2)){
			if(!in_array($row2['Name'], $array2)){
				echo("&nbsp;".$row2['Name']);
				if($row2['tmpGoal']>1){
					echo("*".$row2['tmpGoal']);
				}
			}
		}
		echo("</div><p></p>");
		if($res1['tmpresGoal']>$res2['tmpresGoal']){
			$charres1=$res1['rescharResult']."W";
			$charres2=$res2['rescharResult']."L";
		}
		elseif($res1['tmpresGoal']<$res2['tmpresGoal']){
			$charres1=$res1['rescharResult']."L";
			$charres2=$res2['rescharResult']."W";
		}
		else{
			$charres1=$res1['rescharResult']."D";
			$charres2=$res2['rescharResult']."D";
		}
		mysqli_query($conn,"UPDATE teams SET preresRank=".$res1['resRank'].",rescharResult='".$charres1."' WHERE Abbr='".$team1."'");
		mysqli_query($conn,"UPDATE teams SET preresRank=".$res2['resRank'].",rescharResult='".$charres2."' WHERE Abbr='".$team2."'");
	}
	echo("</body></html>");
$file="History/FMLlive_".$round.".html";
$handle=fopen($file,'w');
$ob=ob_get_contents();
fwrite($handle, $ob);
fclose($handle);
ob_end_clean();
//以下清空teams数据库的临时数据，并更新排名
$result=mysqli_query($conn,"SELECT Abbr FROM teams ORDER BY Points DESC,Goalfor DESC,Goalagainst DESC");
$n=1;
while($array=mysqli_fetch_assoc($result)){
	mysqli_query($conn,"UPDATE teams SET Rank=".$n.",tmpCode=0,tmpGoal=0,tmpresGoal=0 WHERE Abbr='".$array['Abbr']."'");
	$n=$n+1;
}
$resresult=mysqli_query($conn,"SELECT Abbr FROM teams ORDER BY resPoints DESC,resGoalfor DESC,resGoalagainst DESC");
$n=1;
while($array=mysqli_fetch_assoc($resresult)){
	mysqli_query($conn,"UPDATE teams SET resRank=".$n." WHERE Abbr='".$array['Abbr']."'");
	$n=$n+1;
}
//输出本轮积分榜
ob_start();
echo("
<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<title>第".$round."轮积分榜</title>
</head>
<body>
	<h2>一线队积分榜</h2>
	<table>
		<tr><th>球队</th><th>排名</th><th>胜</th><th>平</th><th>负</th><th>进球</th><th>失球</th><th>积分</th><th>近期</th><th>排名变化</th></tr>");
		$result=mysqli_query($conn,"SELECT Abbr,Rank,Win,Draw,Lose,Goalfor,Goalagainst,Points,preRank,charResult FROM teams ORDER BY Rank");
		$n=1;
		while($row=mysqli_fetch_assoc($result)){
			//计算近期战绩字段，如果连局大于等于5则特殊显示
			if(strlen($row['charResult'])<=5){
				$charres=$row['charResult'];
			}
			else{
				$charres=substr($row['charResult'],-5);
				if(strspn($charres, "W")==5 || strspn($charres, "D")==5 || strspn($charres, "L")==5 ){
					$c=$charres[0];
					$num=0;
					$sum=strlen($row['charResult']);
					while($sum-1-$num>=0 && $row['charResult'][$sum-1-$num]==$c)
						$num=$num+1;
					$charres=$c." - ".$num;
				}
			}
			echo("<tr><td>".$row['Abbr']."</td><td>".$n."</td><td>".$row['Win']."</td><td>".$row['Draw']."</td><td>".$row['Lose']."</td><td>".$row['Goalfor']."</td><td>".$row['Goalagainst']."</td><td>".$row['Points']."</td><td>".$charres."</td><td>".($row['preRank']-$row['Rank'])."</td></tr>");
			$n=$n+1;
		}
	echo("</table>
	<h2>预备队积分榜</h2>
	<table>
		<tr><th>球队</th><th>排名</th><th>胜</th><th>平</th><th>负</th><th>进球</th><th>失球</th><th>积分</th><th>近期</th><th>排名变化</th></tr>");
		$result=mysqli_query($conn,"SELECT Abbr,resRank,resWin,resDraw,resLose,resGoalfor,resGoalagainst,resPoints,preresRank,rescharResult FROM teams ORDER BY resRank");
		$n=1;
		while($row=mysqli_fetch_assoc($result)){
			if(strlen($row['rescharResult'])<5){
				$charres=$row['rescharResult'];
			}
			else{
				$charres=substr($row['rescharResult'],-5);
				if(strspn($charres, "W")==5 || strspn($charres, "D")==5 || strspn($charres, "L")==5 ){
					$c=$charres[0];
					$num=0;
					$sum=strlen($row['rescharResult']);
					while($sum-1-$num>=0 && $row['rescharResult'][$sum-1-$num]==$c)
						$num=$num+1;
					$charres=$c." - ".$num;
				}
			}
			echo("<tr><td>".strtolower($row['Abbr'])."</td><td>".$n."</td><td>".$row['resWin']."</td><td>".$row['resDraw']."</td><td>".$row['resLose']."</td><td>".$row['resGoalfor']."</td><td>".$row['resGoalagainst']."</td><td>".$row['resPoints']."</td><td>".$charres."</td><td>".($row['preresRank']-$row['resRank'])."</td></tr>");
			$n=$n+1;
		}
	echo("</table>
</body>
</html>");
$file="History/league_table_".$round.".html";
$handle=fopen($file,'w');
$ob=ob_get_contents();
fwrite($handle, $ob);
fclose($handle);
ob_end_clean();
//下面更新current数据库并输出射手榜
$tmpscoredplayer=mysqli_query($conn,"SELECT Name,Goal,resGoal,tmpGoal,Team FROM current WHERE tmpGoal>0");
$query=mysqli_query($conn,"SELECT Abbr,Lineup FROM teams");
$lineup=array();
//每个队首发阵容，导入一个大列表中
while($res=mysqli_fetch_assoc($query)){
	$lineup[$res['Abbr']]=explode(" ",str_replace("\"","",str_replace("/", " ", $res['Lineup'])));
}
//对每个球员，判断其临时进球是一线队还是预备队的，并更新进球
while($res=mysqli_fetch_assoc($tmpscoredplayer)){
	if(in_array($res['Name'], $lineup[$res['Team']])){
		mysqli_query($conn,"UPDATE current SET Goal=".$res['Goal']."+".$res['tmpGoal']." WHERE Name='".$res['Name']."'");
		mysqli_query($conn,"UPDATE current SET tmpGoal=0 WHERE Name='".$res['Name']."'");
	}
	else{
		mysqli_query($conn,"UPDATE current SET resGoal=".$res['resGoal']."+".$res['tmpGoal']." WHERE Name='".$res['Name']."'");
		mysqli_query($conn,"UPDATE current SET tmpGoal=0 WHERE Name='".$res['Name']."'");
	}
}
//导出射手榜
ob_start();
	echo("
<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<title>第".$round."轮射手榜</title>
</head>
<body>
	<h2>一线队射手榜</h2>
	<table>
		<tr><th>球员</th><th>球队</th><th>进球</th></tr>");
	$result=mysqli_query($conn,"SELECT Name,OwnerNum,Owner1,Owner2,Owner3,Goal FROM current WHERE Goal>0 ORDER BY Goal DESC");
		while($row=mysqli_fetch_assoc($result)){
			echo("<tr><td>".$row['Name']."</td><td>");
			if($row['OwnerNum']==3){
				echo($row['Owner1']."&".$row['Owner2']."&".$row['Owner3']);
			}
			elseif ($row['OwnerNum']==2) {
				echo($row['Owner1']."&".$row['Owner2']);
			}
			else
				echo($row['Owner1']);
			echo("</td><td>".$row['Goal']."</td></tr>");
		}
	echo("</table>
		<p></p>
	<h2>预备队射手榜</h2>
	<table>
		<tr><th>球员</th><th>球队</th><th>进球</th></tr>");
		$result=mysqli_query($conn,"SELECT Name,OwnerNum,Owner1,Owner2,Owner3,resGoal FROM current WHERE resGoal>0 ORDER BY resGoal DESC");
		while($row=mysqli_fetch_assoc($result)){
			echo("<tr><td>".$row['Name']."</td><td>");
			if($row['OwnerNum']==3){
				echo(strtolower($row['Owner1']."&".$row['Owner2']."&".$row['Owner3']));
			}
			elseif ($row['OwnerNum']==2) {
				echo(strtolower($row['Owner1']."&".$row['Owner2']));
			}
			else
				echo(strtolower($row['Owner1']));
			echo("</td><td>".$row['resGoal']."</td></tr>");
		}
	echo("</table>
</body>
</html>");
$file="History/top_goalscorers_".$round.".html";
$handle=fopen($file,'w');
$ob=ob_get_contents();
fwrite($handle, $ob);
fclose($handle);
ob_end_clean();
//设置status数据库的状态，表示比赛结束
mysqli_query($conn,"UPDATE status SET MATCH_ON=0,LAST_SCORED_PLAYER=NULL WHERE Activity='FML'");
mysqli_close($conn);
$file=fopen("logs.txt", "a");
fwrite($file,"Submit round at ".date('Y-m-d H:i:s',time()+8*3600)."\n");
fclose($file);
echo("<script> alert('已完成！'); </script>");
//输出一个很简单的网页，供查看结果
echo("
<!DOCTYPE html>
<html>
<head>
	<title>已完成导入</title>
</head>
<body>
	<p>直播帖已保存在<a href='History/FMLlive_".$round.".html'>链接</a></p>
	<p>积分榜已保存在<a href='History/league_table_".$round.".html'>链接</a></p>
	<p>射手榜已保存在<a href='History/top_goalscorers_".$round.".html'>链接</a></p>
	<a href='index.php'>回到首页</a>
</body>
</html>");
}
else{
	echo("<script>alert('没有权限！');window.close();</script>");
}
?>

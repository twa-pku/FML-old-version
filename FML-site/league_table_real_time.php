<?php
$conn=mysqli_connect("localhost","guest","","fml",'3306','/var/lib/mysql/mysql.sock');
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
if(mysqli_num_rows(mysqli_query($conn,"SELECT * FROM teams WHERE tmpCode>0"))==0){//本轮比赛是否正在进行
	//若不正在进行，则输出已保存的最新积分榜
	$round=mysqli_fetch_assoc(mysqli_query($conn,"SELECT round FROM teams WHERE Rank=1"))['round'];
	echo("<script>location.href='History/league_table_".$round.".html';</script>");
}
//否则从数据库中输出积分榜
else{
	echo("
<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<title>实时积分榜</title>
</head>
<body>
	<h2>一线队积分榜</h2>
	<table>
		<tr><th>球队</th><th>排名</th><th>胜</th><th>平</th><th>负</th><th>进球</th><th>失球</th><th>积分</th><th>近期</th><th>排名变化</th></tr>");
		$result=mysqli_query($conn,"SELECT Abbr,Win,Draw,Lose,Goalfor,Goalagainst,Points FROM teams ORDER BY Points DESC,Goalfor DESC,Goalagainst DESC");
		$n=1;
		while($row=mysqli_fetch_assoc($result)){
			echo("<tr><td>".$row['Abbr']."</td><td>".$n."</td><td>".$row['Win']."</td><td>".$row['Draw']."</td><td>".$row['Lose']."</td><td>".$row['Goalfor']."</td><td>".$row['Goalagainst']."</td><td>".$row['Points']."</td></tr>");
			$n=$n+1;
		}
	echo("</table>
	<h2>预备队积分榜</h2>
	<table>
		<tr><th>球队</th><th>排名</th><th>胜</th><th>平</th><th>负</th><th>进球</th><th>失球</th><th>积分</th><th>近期</th><th>排名变化</th></tr>");
		$result=mysqli_query($conn,"SELECT Abbr,resWin,resDraw,resLose,resGoalfor,resGoalagainst,resPoints FROM teams ORDER BY resPoints DESC,resGoalfor DESC,resGoalagainst DESC");
		$n=1;
		while($row=mysqli_fetch_assoc($result)){
			echo("<tr><td>".strtolower($row['Abbr'])."</td><td>".$n."</td><td>".$row['resWin']."</td><td>".$row['resDraw']."</td><td>".$row['resLose']."</td><td>".$row['resGoalfor']."</td><td>".$row['resGoalagainst']."</td><td>".$row['resPoints']."</td></tr>");
			$n=$n+1;
		}
	echo("</table>
</body>
</html>");
}
mysqli_close($conn);
?>

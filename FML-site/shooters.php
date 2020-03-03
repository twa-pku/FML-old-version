<?php
$conn=mysqli_connect("localhost","guest","","fml",'3306','/var/lib/mysql/mysql.sock');
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
//这个函数不是实时的，考虑到玩家对于实时射手榜的要求可能不是很高
$round=mysqli_fetch_assoc(mysqli_query($conn,"SELECT round FROM teams WHERE Rank=1"))['round'];
echo("<script>location.href='History/top_goalscorers_".$round.".html';</script>");
//未来可以做成只显示进球数最多的一部分球员
/*未来可能可以做成实时的
	echo("
<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<title>最新射手榜</title>
</head>
<body>
	<h2>一线队射手榜</h2>
	<table>
		<tr><th>球员</th><th>球队</th><th>进球</th></tr>");
	$result=mysqli_query($conn,"SELECT Name,OwnerNum,Owner1,Owner2,Owner3,Goal FROM current WHERE Goal>0 ORDER BY Goal DESC");
		while($row=mysqli_fetch_assoc($result)){
			echo("<tr><td>".$row['Name']."</td><td>");
			if($row['OwnerNum']==3){//判断球员在多少个球队效力过，把所有效力过的球队都加上去
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
</html>");*/
	mysqli_close($conn);
?>

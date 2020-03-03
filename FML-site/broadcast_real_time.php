<html>
<head>
	<meta charset="utf-8">
	<title>实时直播贴</title>
</head>
<body>
	<?php
	$conn=mysqli_connect("localhost","guest","","fml",'3306','/var/lib/mysql/mysql.sock');
	if(!$conn){
		die('Could not connect: ' . mysqli_error($conn));
	}
	//非比赛时间所有tmpcode都是0
	if(mysqli_num_rows(mysqli_query($conn,"SELECT Round FROM teams WHERE tmpCode=1"))==0){
		echo("<script> alert('目前为非比赛时间！');history.back(); </script>");
	}
	else{
	echo("<h1>[FML]第".(mysqli_fetch_assoc(mysqli_query($conn,"SELECT Round FROM teams WHERE Rank=1"))['Round']+1)."轮双线直播帖&首发阵容</h1>
	<div>一线队</div>");
	//输出一线队比分
	for($i=0;$i<8;$i++){//共8场比赛
		echo("<div>");
		$res1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Rank,tmpGoal,Lineup FROM teams WHERE tmpCode=".(1+2*$i)));
		$res2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Rank,tmpGoal,Lineup FROM teams WHERE tmpCode=".(2+2*$i)));
		$team1=$res1['Abbr'];
		//输出格式：球队进球数-空格*1-球队名-(球队开赛前排名)-空格*4-进球球员(两两之间空一格)
		echo($res1['tmpGoal']);
		echo("&nbsp;");
		echo($team1);
		echo("(");
		$rank1=$res1['Rank'];
		if($rank1<10)
			echo("0");//保证排名始终占两个字符
		echo($rank1);
		echo(")");
		echo("&nbsp;&nbsp;&nbsp;&nbsp;");
		$array1=explode(" ", str_replace("\"","",str_replace("/", " ", $res1['Lineup'])));//把阵容中的'/'替换为空格，再转换为列表
		$players1=mysqli_query($conn,"SELECT tmpGoal,Name FROM current WHERE Team='".$team1."' AND tmpGoal>0");//找出球队中所有本轮进了球的球员
		while($row1=mysqli_fetch_assoc($players1)){//判断是一线队进球还是预备队进球
			if(in_array($row1['Name'], $array1)){
				echo("&nbsp;".$row1['Name']);
				if($row1['tmpGoal']>1){
					echo("*".$row1['tmpGoal']);
				}
			}
		}
		//球队1输出完毕，下面开始输出球队2，固定球队2是球队1的本轮对手。输出形式和内容和之前一样
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
		//每场比赛之间空一行
		echo("</div><p></p>");
	}
	//下面开始输出首发阵容，比赛之间格式和之前相同
	echo("首发阵容");
	for($i=0;$i<8;$i++){
		$res1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Lineup FROM teams WHERE tmpCode=".(1+2*$i)));
		$res2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,Lineup FROM teams WHERE tmpCode=".(2+2*$i)));
		//输出格式：球队名称-空格*1-球队阵容
		echo("<div>");
		echo($res1['Abbr']);
		echo("&nbsp;");
		echo(str_replace("\"","",$res1['Lineup']));
		echo("</div>");
		echo("<div>");
		echo($res2['Abbr']);
		echo("&nbsp;");
		echo(str_replace("\"","",$res2['Lineup']));
		echo("</div><p></p>");
	}
	//下面开始输出预备队比分，格式和一线队完全相同，除了队名小写以外
	echo("预备队");
	for($i=0;$i<8;$i++){
		echo("<div>");
		$res1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,resRank,tmpresGoal,Lineup FROM teams WHERE tmpCode=".(1+2*$i)));
		$res2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Abbr,resRank,tmpresGoal,Lineup FROM teams WHERE tmpCode=".(2+2*$i)));
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
	}
}
	mysqli_close($conn);
	?>
</body>
</html>

<?php
if(isset($_COOKIE['username']) && $_COOKIE['username']=='root'){
echo("<html>
<head>
	<meta charset='utf-8'>
	<title>直播贴</title>
</head>
<body>
	<h1>[FML]第");
	echo($_POST["round"]);
	echo("轮双线直播帖&首发阵容</h1>
	<div>一线队</div>");
	//这个文件的任务有两个：填写数据库中所有和一轮比赛相关的字段（清理上一轮的信息已经在submit_round文件中做了），和输出BBS风格的直播帖以便复制粘贴到版面
	$conn=mysqli_connect("localhost","admin","","fml",'3306','/var/lib/mysql/mysql.sock');
	if(!$conn){
		die('Could not connect: ' . mysqli_error($conn));
	}
		//要写入数据库的信息：每个球队的对手，每个球队的首发，每个球队的当轮编号，输出BBS风格的直播帖
		//开始输出一线队结果
		//输出格式：球队1(排名)-空格*4-进球-空格*1-“-”号-空格*1-球队2进球-空格*4-球队2(排名)-空格*8-球队3(排名)-空格*4-进球-空格*1-“-”号-空格*1-球队4进球-空格*4-球队4(排名)-后面空一行，由于这个文件是提交直播帖，比赛还没开始，故所有进球字段都是0
		mysqli_query($conn,"START TRANSACTION");
		for($i=0;$i<4;$i++){
			echo("<p>");
			$team1=$_POST["team".(1+4*$i)];
			$team2=$_POST["team".(2+4*$i)];
			echo($team1);
			echo("(");
			$rank1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Rank FROM teams WHERE Abbr='".$team1."'"))['Rank'];
			if($rank1<10)
				echo("0");
			echo($rank1);
			echo(")");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			echo("0");
			echo("&nbsp;-&nbsp;");
			echo("0");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			//初始化teams数据库信息，目前所有球队都是0-0平局，平局数+1，积分+1
			$draw1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Draw,resDraw,Points,resPoints FROM teams WHERE Abbr='".$team1."'"));
			$draw2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Draw,resDraw,Points,resPoints FROM teams WHERE Abbr='".$team2."'"));
			//下面两行是更新球队1和2的本轮对手，临时号情况
			//临时号标定了球队在直播帖上的位置，之后会用来确定球队本轮对手
			mysqli_query($conn,"UPDATE teams SET Opponent='".$team2."',tmpCode=".(1+4*$i).",Draw=".($draw1['Draw']+1).",resDraw=".($draw1['resDraw']+1).",Points=".($draw1['Points']+1).",resPoints=".($draw1['resPoints']+1)." WHERE Abbr='".$team1."'");
			mysqli_query($conn,"UPDATE teams SET Opponent='".$team1."',tmpCode=".(2+4*$i).",Draw=".($draw2['Draw']+1).",resDraw=".($draw2['resDraw']+1).",Points=".($draw2['Points']+1).",resPoints=".($draw2['resPoints']+1)." WHERE Abbr='".$team2."'");
			echo($team2);
			echo("(");
			$rank2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Rank FROM teams WHERE Abbr='".$team2."'"))['Rank'];
			if($rank2<10)
				echo("0");
			echo($rank2);
			echo(")");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
			$team3=$_POST["team".(3+4*$i)];
			$team4=$_POST["team".(4+4*$i)];
			echo($team3);
			echo("(");
			$rank3=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Rank FROM teams WHERE Abbr='".$team3."'"))['Rank'];
			if($rank3<10)
				echo("0");
			echo($rank3);
			echo(")");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			echo("0");
			echo("&nbsp;-&nbsp;");
			echo("0");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			echo($team4);
			echo("(");
			$rank4=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Rank FROM teams WHERE Abbr='".$team4."'"))['Rank'];
			if($rank4<10)
				echo("0");
			echo($rank4);
			echo(")");
			echo("</p><p></p>");
			$draw3=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Draw,resDraw,Points,resPoints FROM teams WHERE Abbr='".$team3."'"));
			$draw4=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Draw,resDraw,Points,resPoints FROM teams WHERE Abbr='".$team4."'"));
			mysqli_query($conn,"UPDATE teams SET Opponent='".$team4."',tmpCode=".(3+4*$i).",Draw=".($draw3['Draw']+1).",resDraw=".($draw3['resDraw']+1).",Points=".($draw3['Points']+1).",resPoints=".($draw3['resPoints']+1)." WHERE Abbr='".$team3."'");
			mysqli_query($conn,"UPDATE teams SET Opponent='".$team3."',tmpCode=".(4+4*$i).",Draw=".($draw4['Draw']+1).",resDraw=".($draw4['resDraw']+1).",Points=".($draw4['Points']+1).",resPoints=".($draw4['resPoints']+1)." WHERE Abbr='".$team4."'");
		}
		//开始输出首发阵容
		echo("首发阵容");
		for($i=0;$i<8;$i++){
			//格式：球队名-空格或*-球队阵容
			echo("<div>");
			$team1=$_POST["team".(1+2*$i)];
			echo($team1);
			$lineup1=$_POST["squad".(1+2*$i)];
			if($lineup1==""){//判断球队本轮有没有发阵容，没发则字符串为空，那么沿用上一轮阵容且在阵容前加*号
				echo("*");
				echo(str_replace("\"","",mysqli_fetch_assoc(mysqli_query($conn,"SELECT Lineup FROM teams WHERE Abbr='".$team1."'"))['Lineup']));
			}
			else{//否则加空格，且更新数据库中的阵容
				echo(" ");
				echo($lineup1);
				mysqli_query($conn,"UPDATE teams SET Lineup='".$lineup1."' WHERE Abbr='".$team1."'");
			}
			echo("</div>");
			echo("<div>");
			$team2=$_POST["team".(2+2*$i)];
			echo($team2);
			$lineup2=$_POST["squad".(2+2*$i)];
			if($lineup2==""){
				echo("*");
				echo(str_replace("\"","",mysqli_fetch_assoc(mysqli_query($conn,"SELECT Lineup FROM teams WHERE Abbr='".$team2."'"))['Lineup']));
			}
			else{
				echo(" ");
				echo($lineup2);
				mysqli_query($conn,"UPDATE teams SET Lineup='".$lineup2."' WHERE Abbr='".$team2."'");
			}
			echo("</div><p></p>");
		}
		//开始输出预备队比分，格式和一线队完全相同，除了队名小写以外
		echo("预备队");
		for($i=0;$i<4;$i++){
			echo("<div>");
			$team1=$_POST["team".(1+4*$i)];
			echo(strtolower($team1));
			echo("(");
			$rank1=mysqli_fetch_assoc(mysqli_query($conn,"SELECT resRank FROM teams WHERE Abbr='".$team1."'"))['resRank'];
			if($rank1<10)
				echo("0");
			echo($rank1);
			echo(")");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			echo("0");
			echo("&nbsp;-&nbsp;");
			echo("0");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			$team2=$_POST["team".(2+4*$i)];
			echo(strtolower($team2));
			echo("(");
			$rank2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT resRank FROM teams WHERE Abbr='".$team2."'"))['resRank'];
			if($rank2<10)
				echo("0");
			echo($rank2);
			echo(")");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
			$team3=$_POST["team".(3+4*$i)];
			echo(strtolower($team3));
			echo("(");
			$rank3=mysqli_fetch_assoc(mysqli_query($conn,"SELECT resRank FROM teams WHERE Abbr='".$team3."'"))['resRank'];
			if($rank3<10)
				echo("0");
			echo($rank3);
			echo(")");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			echo("0");
			echo("&nbsp;-&nbsp;");
			echo("0");
			echo("&nbsp;&nbsp;&nbsp;&nbsp;");
			$team4=$_POST["team".(4+4*$i)];
			echo(strtolower($team4));
			echo("(");
			$rank4=mysqli_fetch_assoc(mysqli_query($conn,"SELECT resRank FROM teams WHERE Abbr='".$team4."'"))['resRank'];
			if($rank4<10)
				echo("0");
			echo($rank4);
			echo(")");
			echo("</div><p></p>");
		}
		//更新status数据库，宣布比赛开始
		mysqli_query($conn,"UPDATE status SET LAST_MODIFIED=".time().",MATCH_ON=1 WHERE Activity='FML'");
		mysqli_query($conn,"COMMIT");
	mysqli_close($conn);
	//在logs中写入比赛开始信息
	$file=fopen("logs.txt", "a");
	fwrite($file,"Start round at ".date('Y-m-d H:i:s',time()+8*3600)."\n");
	fclose($file);
	echo("<a href='index.php'>回到主页</a>
</body>
</html>");
}
else{
	echo("没有权限");
}
?>

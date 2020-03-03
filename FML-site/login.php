<?php
if(isset($_COOKIE['username']) && $_COOKIE['username']=='admin'){
		echo("<script>window.open('index.php','_self');</script>");
	}
else{
	echo("
<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<title>FML site login</title>
</head>
<body>
	用户名：<input type='text' name='user' id='user'>
	密码：<input type='password' name='password' id='password'>
	<input type='button' value='登录' onclick=\"login_handler(document.getElementById('user').value,document.getElementById('password').value)\">
	<a href='index.php'>游客</a>
<script type='text/javascript' src='jsencrypt.min.js'></script>
<script type='text/javascript'>
	function login_handler(username,password){
		var encrypt=new JSEncrypt();
		var PUBLIC_KEY='';
		encrypt.setPublicKey('-----BEGIN PUBLIC KEY-----'+PUBLIC_KEY+'-----END PUBLIC KEY-----');
		password=encrypt.encrypt(password);
		var http=new XMLHttpRequest();
		http.onreadystatechange=function(){
				if(http.readyState==4 && http.status==200){
					if(http.responseText=='1'){
						window.open('index.php','_self');
					}
					else
						alert(http.responseText);
				}
			}
		http.open('POST','login_handler.php',true);
		http.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		http.send('user='+username+'&password='+password);
	}
</script>
</body>
</html>
");
}
?>

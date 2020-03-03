<?php
//用RSA解密，再与数据库储存的md5形式的密码对比
$str=file_get_contents("php://input");
$arr=explode("&",$str);
$arr1=explode("=",$arr[0]);
$arr2=explode("=",$arr[1]);
$username=$arr1[1];
$password=$arr2[1];
$password=base64_decode(str_replace(" ", "+", $password));
$conn=mysqli_connect("localhost","guest","","fml",'3306','/var/lib/mysql/mysql.sock');
if(!$conn){
	die('Could not connect: ' . mysqli_error($conn));
}
$query=mysqli_query($conn,"SELECT password FROM users WHERE username='".$username."'");
if(mysqli_num_rows($query)!=1){
	echo("用户名不存在");
	return;
}
$password_sql=mysqli_fetch_assoc($query)['password'];
$privatekey='-----BEGIN RSA PRIVATE KEY-----

-----END RSA PRIVATE KEY-----';
$privatekey=openssl_pkey_get_private($privatekey);
openssl_private_decrypt($password, $password_de, $privatekey);
if(md5($password_de)==$password_sql){
	setcookie('username',$username,time()+3600);
	echo("1");
}
else{
	echo("密码错误");
}
?>

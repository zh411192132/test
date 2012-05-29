<?php
/**
 * UCenter 应用程序开发 API Example
 *
 * 此文件为 api/uc.php 文件的开发样例，用户处理 UCenter 通知给应用程序的任务
 */

define('UC_VERSION', '1.0.0');		//UCenter 版本标识

define('API_DELETEUSER', 1);		//用户删除 API 接口开关
define('API_RENAMEUSER', 1);		//用户改名 API 接口开关
define('API_UPDATEPW', 1);		//用户改密码 API 接口开关
define('API_GETTAG', 0);		//获取标签 API 接口开关
define('API_SYNLOGIN', 1);		//同步登录 API 接口开关
define('API_SYNLOGOUT', 1);		//同步登出 API 接口开关
define('API_UPDATEBADWORDS', 1);	//更新关键字列表 开关
define('API_UPDATEHOSTS', 1);		//更新域名解析缓存 开关
define('API_UPDATEAPPS', 1);		//更新应用列表 开关
define('API_UPDATECLIENT', 1);		//更新客户端缓存 开关
define('API_UPDATECREDIT', 1);		//更新用户积分 开关
define('API_GETCREDITSETTINGS', 1);	//向 UCenter 提供积分设置 开关
define('API_UPDATECREDITSETTINGS', 1);	//更新应用积分设置 开关

define('API_RETURN_SUCCEED', '1');
define('API_RETURN_FAILED', '-1');
define('API_RETURN_FORBIDDEN', '-2');

error_reporting(7);

define('UC_CLIENT_ROOT', DISCUZ_ROOT.'./client/');
chdir('../');
require_once './config.inc.php';

$code = $_GET['code'];
parse_str(authcode($code, 'DECODE', UC_KEY), $get);
if(MAGIC_QUOTES_GPC) {
	$get = dstripslashes($get);
}

if(time() - $get['time'] > 3600) {
	exit('Authracation has expiried');
}
if(empty($get)) {
	exit('Invalid Request');
}
$action = $get['action'];
$timestamp = time();

//导入文件
if($action=='deleteuser'||$action=='renameuser'||$action=='updatepw'||$action=='synlogin'||$action=='synlogout')
{
	include './class/connect.php';
	include './class/db_sql.php';
	$link=db_connect();
	$empire=new mysqlquery();
}

if($action == 'test') {

	exit(API_RETURN_SUCCEED);

} elseif($action == 'deleteuser') {

	!API_DELETEUSER && exit(API_RETURN_FORBIDDEN);

	//用户删除 API 接口

	$uids = $get['ids'];

	$ur=explode(',',$uids);
	$count=count($ur);
	$b=0;
	for($i=0;$i<$count;$i++)
	{
		//删除短信息
		$userr=$empire->fetch1("select username from {$dbtbpre}enewsmember where userid='".intval($ur[$i])."'");
		$b=1;
		$del=$empire->query("delete from {$dbtbpre}enewsqmsg where to_username='".$userr['username']."' limit 1");
	}
	if($b==1)
	{
		//删除会员
		$sql=$empire->query("delete from {$dbtbpre}enewsmember where userid in ($uids)");
		$sql=$empire->query("delete from {$dbtbpre}enewsmemberadd where userid in ($uids)");
		//删除收藏
		$del=$empire->query("delete from {$dbtbpre}enewsfava where userid in ($uids)");
		$del=$empire->query("delete from {$dbtbpre}enewsfavaclass where userid in ($uids)");
		//删除购买记录
		$del=$empire->query("delete from {$dbtbpre}enewsbuybak where userid in ($uids)");
		//删除下载记录
		$del=$empire->query("delete from {$dbtbpre}enewsdownrecord where userid in ($uids)");
		//删除好友记录
		$del=$empire->query("delete from {$dbtbpre}enewshy where userid in ($uids)");
		$del=$empire->query("delete from {$dbtbpre}enewshyclass where userid in ($uids)");
		//删除留言
		$del=$empire->query("delete from {$dbtbpre}enewsmembergbook where userid in ($uids)");
		//删除反馈
		$del=$empire->query("delete from {$dbtbpre}enewsmemberfeedback where userid in ($uids)");
	}

	exit(API_RETURN_SUCCEED);

} elseif($action == 'renameuser') {

	!API_RENAMEUSER && exit(API_RETURN_FORBIDDEN);

	//用户改名 API 接口
	$uid = $get['uid'];
	$usernamenew = $get['newusername'];
	$usernameold = $get['oldusername'];

	//会员表
	$sql=$empire->query("update {$dbtbpre}enewsmember set username='$usernamenew' where userid='$uid'");
	//短信息
	$sql=$empire->query("update {$dbtbpre}enewsqmsg set to_username='$usernamenew' where to_username='$usernameold'");
	$sql=$empire->query("update {$dbtbpre}enewsqmsg set from_username='$usernamenew' where from_username='$usernameold'");
	//收藏
	$sql=$empire->query("update {$dbtbpre}enewsfava set username='$usernamenew' where userid='$uid'");
	//购买记录
	$sql=$empire->query("update {$dbtbpre}enewsbuybak set username='$usernamenew' where userid='$uid'");
	//下载记录
	$sql=$empire->query("update {$dbtbpre}enewsdownrecord set username='$usernamenew' where userid='$uid'");
	//信息表
	$tbsql=$empire->query("select tbname from {$dbtbpre}enewstable");
	while($tbr=$empire->fetch($tbsql))
	{
		$usql=$empire->query("update {$dbtbpre}ecms_".$tbr['tbname']." set username='$usernamenew' where userid='$uid' and ismember=1");
	}

	exit(API_RETURN_SUCCEED);

} elseif($action == 'updatepw') {

	!API_UPDATEPW && exit(API_RETURN_FORBIDDEN);

	//更改用户密码
	$username=$get['username'];
	$password=md5($get['password']);
	$sql=$empire->query("update {$dbtbpre}enewsmember set password='$password' where username='$username' limit 1");

	exit(API_RETURN_SUCCEED);

} elseif($action == 'gettag') {

	!API_GETTAG && exit(API_RETURN_FORBIDDEN);

	//获取标签 API 接口
	exit(API_RETURN_SUCCEED);

} elseif($action == 'synlogin' && $_GET['time'] == $get['time']) {

	!API_SYNLOGIN && exit(API_RETURN_FORBIDDEN);

	//同步登录 API 接口

	$uid = intval($get['uid']);
	
	$now = date("Y-m-d H:i:s");
	$empire->query("insert into test (create_time) values ('{$now}')");

	$ur=$empire->fetch1("select userid,username,groupid from {$dbtbpre}enewsmember where userid='$uid'");
	$logincookie=time()+86400*365;//cookie保存时间
	if($ur['userid'])
	{
		$rnd=make_password(12);
		//默认会员组
		if(empty($ur['groupid']))
		{
			$ur['groupid']=$public_r['defaultgroupid'];
		}
		$usql=$empire->query("update {$dbtbpre}enewsmember set rnd='$rnd',groupid='$ur[groupid]' where userid='$uid'");
		header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
		$set1=esetcookie("mlusername",$ur['username'],$logincookie);
		$set2=esetcookie("mluserid",$ur['userid'],$logincookie);
		$set3=esetcookie("mlgroupid",$ur['groupid'],$logincookie);
		$set4=esetcookie("mlrnd",$rnd,$logincookie);
		esetcookie("mldoactive","",0);
	}
	else
	{
		$set5=esetcookie("mldoactive",$uid,$logincookie);
	}

} elseif($action == 'synlogout') {

	!API_SYNLOGOUT && exit(API_RETURN_FORBIDDEN);

	//同步登出 API 接口
	header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
	$set1=esetcookie("mlusername","",0);
	$set2=esetcookie("mluserid","",0);
	$set3=esetcookie("mlgroupid","",0);
	$set4=esetcookie("mlrnd","",0);

} elseif($action == 'updatebadwords') {

	!API_UPDATEBADWORDS && exit(API_RETURN_FORBIDDEN);

	//更新关键字列表
	exit(API_RETURN_SUCCEED);

} elseif($action == 'updatehosts') {

	!API_UPDATEHOSTS && exit(API_RETURN_FORBIDDEN);

	//更新HOST文件
	exit(API_RETURN_SUCCEED);

} elseif($action == 'updateapps') {

	!API_UPDATEAPPS && exit(API_RETURN_FORBIDDEN);

	//更新应用列表
	exit(API_RETURN_SUCCEED);

} elseif($action == 'updateclient') {

	!API_UPDATECLIENT && exit(API_RETURN_FORBIDDEN);

	//更新客户端缓存
	exit(API_RETURN_SUCCEED);

} elseif($action == 'updatecredit') {

	!UPDATECREDIT && exit(API_RETURN_FORBIDDEN);

	//更新用户积分
	exit(API_RETURN_SUCCEED);

} elseif($action == 'getcreditsettings') {

	!GETCREDITSETTINGS && exit(API_RETURN_FORBIDDEN);

	//向 UCenter 提供积分设置
	echo uc_serialize($credits);

} elseif($action == 'updatecreditsettings') {

	!API_UPDATECREDITSETTINGS && exit(API_RETURN_FORBIDDEN);

	//更新应用积分设置
	exit(API_RETURN_SUCCEED);

} else {

	exit(API_RETURN_FAILED);

}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	$ckey_length = 4;

	$key = md5($key ? $key : UC_KEY);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

function dsetcookie($var, $value, $life = 0, $prefix = 1) {
	global $cookiedomain, $cookiepath, $timestamp, $_SERVER;
	setcookie($var, $value,
		$life ? $timestamp + $life : 0, $cookiepath,
		$cookiedomain, $_SERVER['SERVER_PORT'] == 443 ? 1 : 0);
}

function dstripslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dstripslashes($val);
		}
	} else {
		$string = stripslashes($string);
	}
	return $string;
}

function uc_serialize($arr, $htmlon = 0) {
	include_once UC_CLIENT_ROOT.'./lib/xml.class.php';
	return xml_serialize($arr, $htmlon);
}

function uc_unserialize($s) {
	include_once UC_CLIENT_ROOT.'./lib/xml.class.php';
	return xml_unserialize($s);
}

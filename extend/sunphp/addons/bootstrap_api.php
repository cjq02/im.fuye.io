<?php

declare(strict_types=1);


use app\admin\model\CoreStorage;


defined('SUN_IN') or exit('Sunphp Access Denied');

global $_W,$_GPC;

$time=time();
// $get=$request->get();
// $post=$request->post();
$header=$request->header();


$module_name=$get['m'];


// 常量定义
!(defined('IA_ROOT')) && define('IA_ROOT', substr(root_path(),0,-1));
!(defined('ATTACHMENT_ROOT')) && define('ATTACHMENT_ROOT',IA_ROOT.DIRECTORY_SEPARATOR.'attachment');
!(defined('MODULE_ROOT')) && define('MODULE_ROOT',IA_ROOT.DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.$module_name);

!(defined('MODULE_URL')) && define('MODULE_URL',$request->domain()."/".'addons'."/".$module_name."/");
!(defined('TIMESTAMP')) && define('TIMESTAMP',$time);
!(defined('CLIENT_IP')) && define('CLIENT_IP',$request->ip());
!(defined('DEVELOPMENT')) && define('DEVELOPMENT',false);


// 默认空
$_W['openid']='';



// 构造$_W参数
$_W['timestamp']=$time;
$_W['clientip']=$request->ip();
$_W['siteroot']=$request->domain()."/";
$_W['siteurl']=$request->domain().$request->url();

// 本地附件url
$_W['attachurl_local']=$_W['siteroot']."attachment/";

// 开启远程就是远程附件地址
$storage=CoreStorage::where('acid',$get['i'])->find();

$sys_storage_set=false;
if(empty($storage)||$storage['type']==1){
    $storage=CoreStorage::where('acid',0)->find();
    $sys_storage_set=true;
}

if(empty($storage)){
    $type=1;
}else{
    $type=$storage->type;
}
switch($type){
    case 1:
        $_W['attachurl']=$_W['attachurl_local'];
        break;
    case 2:
        $oss=$storage->ali_oss;
        $_W['attachurl']=$oss['url'].'/';
        break;
    case 3:
        $oss=$storage->tencent_cos;
        $_W['attachurl']=$oss['url'].'/';
        break;
    case 4:
        $oss=$storage->qiniu;
        $_W['attachurl']=$oss['url'].'/';
        break;
}

$_W['attachurl_remote']=$_W['attachurl'];
$_W['config']['cookie']['pre']='';



//存储的类型
if($type>1){
    $_W['setting']['remote']['type']=$type;
}

$_W['isajax']=$request->isAjax();
$_W['ispost']=$request->isPost();
$_W['sitescheme']=$request->scheme();
$_W['ishttps']=$_W['sitescheme']=='https'?true:false;


$_W['uniacid']=$get['i'];
$_W['acid']=$get['i'];


$_W['current_module']['name']=$module_name;
$_W['current_module']['version']=$module['version'];


// $_W['account']['level']="1";$account包含了level
$_W['account']=$account->toArray();


$_W['account']['acid']=$_W['acid'];


if($sys_storage_set){
    $system_storage=$storage;
}else{
    $system_storage=CoreStorage::where('acid',0)->field(['suffix','img_size','video_size','file_size'])->find();
}
$_W['setting']['upload']['audio']['limit']=$system_storage['video_size'];
$_W['setting']['upload']['video']['limit']=$system_storage['video_size'];
$_W['setting']['upload']['image']['limit']=$system_storage['img_size'];
$_W['setting']['upload']['file']['limit']=$system_storage['file_size'];

$sys_suffix=[];
if(!empty($system_storage['suffix'])){
    $sys_suffix=preg_split("/[\s\r\n]+/",$system_storage->suffix);
}

$_W['setting']['upload']['audio']['extentions']=$sys_suffix;
$_W['setting']['upload']['video']['extentions']=$sys_suffix;
$_W['setting']['upload']['image']['extentions']=$sys_suffix;
$_W['setting']['upload']['file']['extentions']=$sys_suffix;


// 打开的容器
if(empty($header['user-agent'])){
    $ua='';
}else{
    $ua = $header['user-agent'];
}

if(strpos($ua, 'MicroMessenger') == false && strpos($ua, 'Windows Phone') == false){
    //普通浏览器，不区分详细
    $_W['container']="unknown";
}else{
    // 微信浏览器
    $_W['container']="wechat";
}


// $_W['isfounder']
// $_W['role']="mdkeji_im";



// 构造gpc参数
//不带cookie $_GPC = array_merge($_COOKIE,$get, $post);
$_GPC = array_merge($get, $post);

//单独保存post的值
$_GPC['__input'] = $post;


// 转换为Sungpc对象，访问未知属性时，给默认值
// 注意：会导致二维数组无法赋值，如有二维数组赋值，需要注意格式
// 注意：会导致$_GPC['xx']++运算报错！

// 取消自动复制，采用严格模式，未定义数组索引必须判断！
include_once root_path() . 'extend/sunphp/addons/SunGPC.php';
$_GPC=new SunGPC($_GPC);






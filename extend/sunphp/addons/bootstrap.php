<?php

declare(strict_types=1);

use app\admin\model\CoreAccount;
use app\admin\model\CoreApp;
use app\admin\model\CoreBindapp;
use app\admin\model\CoreStorage;
use app\admin\model\CoreUseaccount;
use app\admin\model\CoreUser;
use sunphp\cache\SunCache;

defined('SUN_IN') or exit('Sunphp Access Denied');

// 加载日志功能
if (file_exists(__DIR__ . '/../../sunphp/function/log.php')) {
    require_once __DIR__ . '/../../sunphp/function/log.php';
    
    // 设置默认日志路径
    sun_log_set_path(root_path() . 'runtime/logs/');
    
    // 设置默认日志级别
    sun_log_set_level('debug');
}

global $_W,$_GPC;

$time=time();
$get=$request->get();
$post=$request->post();
$header=$request->header();





// 校验参数正确性
switch($_W['addons_index']){
    case 'app':
        if(empty($get['i'])){
            echo "i参数错误！";
            die();
        }

        session_start();
        if(!empty($_SESSION['fans_core_member_'.$get['i']])){
            $_W['fans']=$_SESSION['fans_core_member_'.$get['i']];

        }else{
            // 可能存在的参数
            $_W['fans']=[
                'uid'=>'',
                'openid'=>'',
                'unionid'=>'',
                'nickname'=>'',
                'avatar'=>'',
                'follow'=>'',
            ];
        }
        session_commit();

        $_W['member']=$_W['fans'];

    break;
    case 'web':
        if(empty($get['i'])){

            //尝试获取来源HTTP_REFERER的i参数
            $cookie_i=cookie('sunphp_addons_uniacid');


            if(isset($_SERVER['HTTP_REFERER'])&&preg_match("/(\?|&)\i=([^&#\/]+)(&|$)/i",$_SERVER['HTTP_REFERER'],$matches)){
                if(!empty($matches[2])&&is_numeric($matches[2])){
                    $cookie_i=$matches[2];
                }
            }

            if(empty($cookie_i)){
                header('Location:'.$request->domain());
                die();
            }else{
                $get['i']=$cookie_i;

                // 将i参数嵌入到request->get()对象里面
                $request->withGet($get);
            }
        }else{

            // 更新i值
            setcookie('sunphp_addons_uniacid',$get['i'],time()+36000);

            // tp6方法设置无效
            // cookie('sunphp_addons_uniacid',$get['i'],36000);

        }



        //初始化page
        if(empty($get['page'])){
            $get['page']=1;
        }

        //表单token值，和checksubmit配合使用
        $_W['token']='sunphp_addons_index';

        // 检查后台用户是否登陆
        $cookie=$request->cookie();
        if(empty($cookie['sunphp_user_session_id'])){
            $sunphp_redirect_url='#/'.urlencode($request->domain().$request->url());
            header('Location:'.$request->domain().$sunphp_redirect_url);
            die();
        }
        //检查用户是否存在
        $user=CoreUser::where('session_id',$cookie['sunphp_user_session_id'])->where('is_delete',0)->find();
        if(empty($user)){
            $sunphp_redirect_url='#/'.urlencode($request->domain().$request->url());
            header('Location:'.$request->domain().$sunphp_redirect_url);
            die();
        }

        //后台登录用户的角色
        $_W['role']='operator';
        $_W['isadmin']=false;
        $_W['isfounder']=false;

        if($user['type']!=2){
            //检查使用者权限
            $use_account=CoreUseaccount::where([
                'uid'=>$user['id'],
                'acid'=>$get['i']
            ])->find();
            if(empty($use_account)){
                // return response('');会导致程序继续执行报错！
                echo "无平台操作权限";
                die();
            }
            $request->use_account=$use_account->toArray();

            if($use_account['role']==2){
                // 平台所有者
                $_W['role']='owner';
                $_W['isadmin']=true;
            }

        }else{
            //后台登录用户的角色
            $_W['role']='founder';
            $_W['isadmin']=true;
            $_W['isfounder']=true;
        }

        //保存在middleware中
        $request->user=$user->toArray();

        //后台登录用户
        $_W['uid']=$user['id'];

        $_W['username']=$user['name'];
        $_W['user']=[
            'uid'=>$user['id'],
            'username'=>$user['name']
        ];


    break;
    default:
    break;
}



if(empty($get['a'])){
    // a可能webapp、wxapp等
    $get['a']='site';
}

if(empty($get['c'])){
    // from=wxapp的时候，c可能是auth，a可能是session，调用的是框架方法，而不是进入应用
    $get['c']='entry';
}


//检查平台
$account=CoreAccount::where('id',$get['i'])->where('is_delete',0)->find();
if(empty($account)){
    echo "平台不存在！";
    die();
}

$request->account=$account->toArray();


if(empty($get['do'])){
    echo "do参数错误！";
    die();
}


// 定义基础常量
!(defined('IA_ROOT')) && define('IA_ROOT', substr(root_path(),0,-1));


// 某些Web操作不需要检查平台和应用的绑定关系
$sunphp_web_nocheck=['utility'];



if(!in_array($get['c'],$sunphp_web_nocheck)){


    // 检查参数
    if(!empty($get['module_name'])){
        $module_name=$get['module_name'];
    }else if(!empty($get['m'])){
        $module_name=$get['m'];
    }else{
        echo "module_name参数错误！";
        die();
    }



    //检查应用
    $module=CoreApp::where(['identity'=>$module_name,'dir'=>'addons'])->find();
    if(empty($module)){
        echo "应用不存在！";
        die();
    }

    $request->app=$module->toArray();


    //检查平台是否绑定应用
    $can_use=CoreBindapp::alias('a')->join('core_supports b','a.sid=b.id')
    ->where(['a.acid'=>$account['id'],'b.app_id'=>$module['id']])->find();
    if(empty($can_use)){
        echo "平台未绑定应用";
        die();
    }


    // 定义参数
    !(defined('MODULE_ROOT')) && define('MODULE_ROOT',IA_ROOT.DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.$module_name);
    !(defined('MODULE_URL')) && define('MODULE_URL',$request->domain()."/".'addons'."/".$module_name."/");


    // 定义参数
    $_W['current_module']['name']=$module_name;
    $_W['current_module']['version']=$module['version'];

}



// 常量定义
!(defined('ATTACHMENT_ROOT')) && define('ATTACHMENT_ROOT',IA_ROOT.DIRECTORY_SEPARATOR.'attachment');

!(defined('TIMESTAMP')) && define('TIMESTAMP',$time);
!(defined('CLIENT_IP')) && define('CLIENT_IP',$request->ip());
!(defined('DEVELOPMENT')) && define('DEVELOPMENT',false);


// 默认空
$_W['openid']='';

// 构造wxapp的参数
if(!empty($get['from'])){
    switch($get['from']){
        case 'wxapp':
            if(!empty($get['state'])&&(strpos($get['state'],'we7sid-')!==false)){
                $wxapp_session=SunCache::get(str_replace('we7sid-','',$get['state']),true);
                if(!empty($wxapp_session)&&!empty($wxapp_session['openid'])){
                    $_W['openid']=$wxapp_session['openid'];

                    $_W['fans']['openid']=$wxapp_session['openid'];

                    // 可能存在的参数
                    if(isset($wxapp_session['unionid'])){
                        $_W['fans']['unionid']=$wxapp_session['unionid'];
                    }

                }
            }
        break;
        default:
        break;
    }
}



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

if(stripos($ua, 'MicroMessenger') == false && stripos($ua, 'Windows Phone') == false){
    if(stripos($ua, 'iphone') !== false){
        $_W['container']="iphone";
    }else  if(stripos($ua, 'ipad') !== false){
        $_W['container']="ipad";
    }else  if(stripos($ua, 'ipod') !== false){
        $_W['container']="ipod";
    }else  if(stripos($ua, 'android') !== false){
        $_W['container']="android";
    }else{
        //普通浏览器，不区分详细
        $_W['container']="unknown";
    }
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






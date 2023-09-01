<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-13 18:21:29
 * @LastEditors: light
 * @LastEditTime: 2023-09-01 14:41:35
 * @Description: SonLight Tech版权所有
 */

 declare(strict_types=1);

 // [ 应用入口文件 ]
namespace think;

use app\admin\model\CoreAccount;
use app\admin\model\CoreApp;
use sunphp\api\SunWxapi;

define('SUN_IN', true);
define('IN_IA', true);
require __DIR__ . '/vendor/autoload.php';


// 执行HTTP应用并响应
$app=new App();
//必须手动初始化，加载配置
$app->initialize();


$request = $app->request;

$get=$request->get();
$post=$request->post();


if(empty($get['id'])){
    echo "id参数错误！";
    die();
}else{
    $get['i']=$get['id'];
    // 将i参数嵌入到request->get()对象里面
    $request->withGet($get);
}

//检查平台
$account=CoreAccount::where('id',$get['i'])->where('is_delete',0)->find();
if(empty($account)){
    echo "平台不存在！";
    die();
}

if(empty($account['token'])){
    echo "平台token未配置！";
    die();
}



$log = $app->log;


$log->write($get);
$log->write($post);

// 检查微信get签名，接入开发者
if($request->isGet()){
    if(SunWxapi::checkSignature($account['token'])){
        echo $_GET['echostr'];
        die();
    }else{
        echo "微信api校验失败！";
        die();
    }
}

// 微信post数据
if($request->isPost()){

}



die();




// 区分模块类型app、addons
$module=CoreApp::where(['identity'=>$module_name,'is_delete'=>0])->find();
if(empty($module)){
    echo "应用不存在！";
    die();
}


/* addons模块的入口地址 */
global $_W,$_GPC;
$_W['addons_index']='api';

// 与thinkphp6冲突的函数，需要提前预定义
// include_once __DIR__ . '/extend/sunphp/addons/functions_conflict.php';

include_once root_path() . 'extend/sunphp/addons/bootstrap.php';



$module_now=$_W['current_module']['name'];
$class_a=ucfirst(strtolower($_GPC['a']));
$class_module=ucfirst(strtolower($module_now)).'Module'.$class_a;






// 兼容数据操作
include_once root_path().'extend/sunphp/function/db_ims.php';

// 兼容常用方法，如message(),load()等等
include_once root_path().'extend/sunphp/addons/functions.php';






if($_GPC['c']=='entry'){

    //执行应用内部逻辑

    //兼容WeAccount::create()->sendTplNotice方法
    include_once root_path().'extend/sunphp/addons/WeAccount.php';

    //引入WeModule，兼容$this->操作方法
    include_once root_path().'extend/sunphp/addons/WeModule'.$class_a.'.php';


    include_once root_path().'addons/'.$module_now.'/'.strtolower($_GPC['a']).'.php';


    $class_now=new $class_module();


    if($class_a=='Site'){
        $method='doMobile'.$_GPC['do'];
    }else{
        // webapp、wxapp等入口
        $method='doPage'.$_GPC['do'];
    }


    if(session_id()){
        // 防止session_start阻塞
        session_commit();
    }

    $result=$class_now->$method();


    echo $result;
    die();

}else{

    //执行框架内逻辑
    include_once root_path().'extend/sunphp/addons/'.strtolower($_GPC['from']).'/'.strtolower($_GPC['c']).'/WeFrame'.$class_a.'.php';
    $class_frame='WeFrame'.$class_a;
    $class_method=strtolower($_GPC['do']);

    $class_frame_instance=new $class_frame();
    $result=$class_frame_instance->$class_method();

    echo $result;
    die();

}














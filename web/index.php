<?php

/*
 * @Author: SonLight Tech
 * @Date: 2023-05-15 11:03:17
 * @LastEditors: light
 * @LastEditTime: 2024-08-06 16:15:03
 * @Description: SonLight Tech版权所有
 */


 declare(strict_types=1);

 // [ 应用入口文件 ]
namespace think;

define('SUN_IN', true);
define('IN_IA', true);
require __DIR__ . '/../vendor/autoload.php';


/* addons模块的入口地址 */
global $_W,$_GPC;
$_W['addons_index']='web';

// 与thinkphp6冲突的函数，需要提前预定义
include_once __DIR__ . '/../extend/sunphp/addons/functions_conflict.php';


// 执行HTTP应用并响应
$app=new App();
//必须手动初始化，加载配置
$app->initialize();


$request = $app->request;
// 设置全局变量过滤，防止输入特殊字符
// $request->filter(['htmlspecialchars']);

include_once root_path() . 'extend/sunphp/addons/bootstrap.php';

// 兼容数据操作
include_once root_path().'extend/sunphp/function/db_ims.php';

// 兼容常用方法，如message(),load()等等
include_once root_path().'extend/sunphp/addons/functions.php';





// 某些Web操作不需要检查平台和应用的绑定关系
$sunphp_web_nocheck=['utility'];


if(!in_array($_GPC['c'],$sunphp_web_nocheck)){


    // 不通过admin模块执行
    $module_now=$_W['current_module']['name'];
    $class_a=ucfirst('site');
    $class_module=ucfirst(strtolower($module_now)).'Module'.$class_a;




    //兼容WeAccount::create()->sendTplNotice方法
    include_once root_path().'extend/sunphp/addons/WeAccount.php';


    //引入WeModule，兼容$this->操作方法

    if($_GPC['do']=='sunphpWelcome'){
        include_once root_path().'extend/sunphp/addons/WeModule.php';
        include_once root_path().'addons/'.$module_now.'/module.php';

        $class_module=ucfirst(strtolower($module_now)).'Module';

        $class_now=new $class_module();
        $method='welcomeDisplay';//自定义后台模块入口

    }else{
        include_once root_path().'extend/sunphp/addons/WeModule'.$class_a.'.php';
        include_once root_path().'addons/'.$module_now.'/site.php';

        $class_now=new $class_module();
        $method='doWeb'.$_GPC['do'];
    }


    if(session_id()){
        // 防止session_start阻塞
        session_commit();
    }

    $result=$class_now->$method();

    echo $result;
    die();

}else{

    $class_a=ucfirst(strtolower($_GPC['a']));


     //执行框架内逻辑
     include_once root_path().'extend/sunphp/addons/web/'.strtolower($_GPC['c']).'/WeFrame'.$class_a.'.php';
     $class_frame='WeFrame'.$class_a;
     $class_method=strtolower($_GPC['do']);

     $class_frame_instance=new $class_frame();
     $result=$class_frame_instance->$class_method();

     echo $result;
     die();

}


















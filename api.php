<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-13 18:21:29
 * @LastEditors: light
 * @LastEditTime: 2023-09-01 18:19:30
 * @Description: SonLight Tech版权所有
 */

 declare(strict_types=1);

 // [ 应用入口文件 ]
namespace think;

use app\admin\model\CoreAccount;
use app\admin\model\CoreApp;
use app\admin\model\CoreBindapp;
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

if(empty($account['api_token'])){
    echo "平台token未配置！";
    die();
}


// $log = $app->log;
// $log->write($get);
// $log->write($post);


// 可能是xml数据
if(empty($post)){
    $postStr = file_get_contents('php://input');
    // $log->write($postStr);
    if(!empty($postStr)){
        try{
            $input_array=simplexml_load_string($postStr,'SimpleXMLElement', LIBXML_NOCDATA);
            $input_array=json_encode($input_array);
            $input_array=json_decode($input_array,true);
            // $log->write($input_array);

            $post=$input_array;
            $request->withPost($post);

        }catch(\Exception $e){
            $log->write($e);
        }
    }
}




// 检查微信get签名，接入开发者
if($request->isGet()){
    if(SunWxapi::checkSignature($account['api_token'])){
        echo $_GET['echostr'];
        die();
    }else{
        echo "微信api校验失败！";
        die();
    }
}

// 微信post数据
if($request->isPost()){
    // 校验签名是否来自微信服务器
    if(!SunWxapi::checkSignature($account['api_token'])){
        echo "微信api校验失败！";
        die();
    }

    // 如果api携带了module参数
    if(!empty($get['m'])){
        //检查应用
        $module=CoreApp::where(['identity'=>$get['m'],'is_delete'=>0])->find();
        if(empty($module)){
            echo "应用不存在！";
            die();
        }

        //检查平台是否绑定应用
        $can_use=CoreBindapp::alias('a')->join('core_supports b','a.sid=b.id')
        ->where(['a.acid'=>$account['id'],'b.app_id'=>$module['id']])->find();
        if(empty($can_use)){
            echo "平台未绑定应用";
            die();
        }

        $request->account=$account->toArray();
        $request->app=$module->toArray();

        switch($module['dir']){
            case 'addons':
                /* addons模块的入口地址 */
                global $_W,$_GPC;
                $_W['addons_index']='api';

                // 与thinkphp6冲突的函数，需要提前预定义
                // include_once __DIR__ . '/extend/sunphp/addons/functions_conflict.php';

                include_once root_path() . 'extend/sunphp/addons/bootstrap_api.php';
                $module_now=$_W['current_module']['name'];
                $class_module=ucfirst(strtolower($module_now)).'ModuleProcessor';

                // 兼容数据操作
                include_once root_path().'extend/sunphp/function/db_ims.php';

                // 兼容常用方法，如message(),load()等等
                include_once root_path().'extend/sunphp/addons/functions.php';

                //兼容WeAccount::create()->sendTplNotice方法
                include_once root_path().'extend/sunphp/addons/WeAccount.php';

                //引入WeModule，兼容$this->操作方法
                include_once root_path().'extend/sunphp/addons/WeModuleProcessor.php';

                include_once root_path().'addons/'.$module_now.'/processor.php';


                $class_now=new $class_module();
                $method='respond';

                if(session_id()){
                    // 防止session_start阻塞
                    session_commit();
                }

                $result=$class_now->$method();

                // echo $result;
                // die();

            break;
            case 'app':
                $request->setPathinfo('Processor/respond');
                // $request->withPost($notify_post);
                $http = $app->http;
                $http->name($get['m']); //指定模块
                $response = $http->run($request);

                //不能输出响应，否则后面代码无法执行
                // $response->send();
                // $http->end($response);

            break;
            default:
            break;
        }


    }else{
        // 无module参数，使用平台配置的自动回复机制
        // 1，关键字回复
        // 2，非关键字回复
        // 3，默认回复

    }

}


echo "success";
die();



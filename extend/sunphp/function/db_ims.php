<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-02-24 14:52:38
 * @LastEditors: light
 * @LastEditTime: 2023-10-20 11:59:34
 * @Description: SonLight Tech版权所有
 */
declare(strict_types=1);
defined('SUN_IN') or exit('Sunphp Access Denied');

use app\admin\model\CorePay;
use sunphp\core\SunHelper;
use think\facade\Db;

/* ims前缀的数据库必须兼容 */

function tableprefix(){
    $prefix='ims_';
    return $prefix;
}

function tablename($table){
    switch($table){
        case 'mc_members':
            return ' sun_core_member ';
        break;
        default:
        break;
    }
    $prefix=tableprefix();
    return ' '.$prefix.$table.' ';
}

// 调试语句
function pdo_debug(){
    return Db::getLastSql();
}

function pdo_begin(){
    // 启动事务
    Db::startTrans();
}

function pdo_startTrans(){
    // 启动事务
    Db::startTrans();
}

function pdo_commit(){
    // 提交事务
    Db::commit();
}

function pdo_rollback(){
    // 回滚事务
    Db::rollback();
}

//一行记录
function pdo_get($table,$con=[],$fields=[]){
    //thinkphp6的name方法自动加前缀
    global $_W;

    // 兼容数据库操作
    switch($table){
        case 'users_permission':
            $menus=SunHelper::getMenus();
            if(empty($menus)){
                return 'no_menus';//无菜单权限
            }

            $menus_str='';
            $menu_pre=$_W['current_module']['name'].'_menu_';
            foreach($menus as $val){
                if(!empty($val)){
                    $menus_str.=$menu_pre.strtolower($val['do']).'|';
                }
            }
            $permissions=[
                'permission'=>$menus_str
            ];
            return $permissions;
        break;
        case 'uni_settings':


        //获取支付参数
        $unisetting['payment']=[];

        if(!empty($con['uniacid'])){
            $params=CorePay::where('acid',$con['uniacid'])->find();
            $params_system=CorePay::where('acid',0)->find();

            if(empty($params)||empty($params['ali_appid'])||empty($params['ali_switch'])){
                if(empty($params_system)||empty($params_system['ali_appid'])||empty($params_system['ali_switch'])){
                    // 支付宝支付未启用
                }else{
                    $unisetting['payment']['alipay']=[
                        'pay_switch'=>true,
                        'account'=>$params_system['ali_appid']
                    ];
                }

            }else{
                $unisetting['payment']['alipay']=[
                    'pay_switch'=>true,
                    'account'=>$params['ali_appid']
                ];
            }

            if(empty($params)||empty($params['wx_mchid'])||empty($params['wx_switch'])){
                if(empty($params_system)||empty($params_system['wx_mchid'])||empty($params_system['wx_switch'])){
                    // 微信支付未启用
                }else{
                    $unisetting['payment']['wechat']=[
                        'pay_switch'=>true,
                        'account'=>$params_system['wx_mchid']
                    ];
                }
            }else{
                $unisetting['payment']['wechat']=[
                    'pay_switch'=>true,
                    'account'=>$params['wx_mchid']
                ];
            }

            if(!empty($unisetting['payment'])){
                $unisetting['payment']=serialize($unisetting['payment']);
            }
        }
        return $unisetting;


        break;
        case 'mc_members':
            // 对应的是sun_core_members表
            return Db::table('sun_core_member')->where($con)->field($fields)->find();
        break;
        default:
        break;
    }

    return Db::table(tableprefix().$table)->where($con)->field($fields)->find();
}

//一行的某个字段值
// function pdo_getvalue($table,$con=[],$value){

//     return Db::table(tableprefix().$table)->where($con)->value($value);
// }
function pdo_getcolumn($table,$con=[],$value){

    return Db::table(tableprefix().$table)->where($con)->value($value);
}

//所有行
function pdo_getall($table,$con=[],$fields=[],$keyfield=''){
    $data=Db::table(tableprefix().$table)->where($con)->field($fields)->select()->toArray();
    if(!empty($keyfield)){
        $result=[];
        foreach($data as $v){
            $result[$v[$keyfield]]=$v;
        }
        return $result;
    }else{
        return $data;
    }
}

//返回添加成功的条数，通常情况返回 1
function pdo_insert($table,$data){
    return Db::table(tableprefix().$table)->insert($data);
}

//插入并返回id
function pdo_insertid($table='',$data=[]){
    if(empty($table)){
        return Db::getPdo()->lastInsertId(null);
    }else{
        return Db::table(tableprefix().$table)->insertGetId($data);
    }
}

//批量插入数据
function pdo_insertall($table,$data){

    return Db::table(tableprefix().$table)->insertAll($data);
}

//返回影响数据的条数
function pdo_update($table,$data,$con=[]){
    switch($table){
        case 'mc_members':
            // 对应的是sun_core_members表
            return Db::table('sun_core_member')->where($con)->update($data);
        break;
        default:
        break;
    }

    // 无条件全部更新
    if(empty($con)){
        return Db::table(tableprefix().$table)->whereRaw('1=1')->update($data);
    }else{
        return Db::table(tableprefix().$table)->where($con)->update($data);
    }
}

function pdo_delete($table,$con=[]){

    return Db::table(tableprefix().$table)->where($con)->delete();
}

//返回第一行
//如果可能有多条数据，必须在sql中使用limit 1来限制数量
function pdo_fetch($sql,$params=[]){
    $res= Db::query($sql,$params);

    if(empty($res)){
        return [];
    }

    //如果为空[]，current返回false，导致模板渲染问题
    return current($res);
}

//返回一个值，比如count(1)，第一行第一列的值
function pdo_fetchcolumn($sql,$params=[]){
   $res=Db::query($sql,$params);
    //这时候返回的是数组
    //如果是空[]
    if(empty($res)){
        return false;
    }
    return current($res[0]);
}

//返回所有行
function pdo_fetchall($sql,$params=[]){
    return Db::query($sql,$params);
}

//增删改
function pdo_query($sql,$params=[]){
    return Db::execute($sql,$params);
}

//安装更新数据库
function pdo_run($sql,$params=[]){
    return Db::execute($sql,$params);
}

//切换连接数据库后执行sql
function pdo_connect_run($sql,$params=[],$database='mysql'){
    return Db::connect($database)->execute($sql,$params);
}

function pdo_tableexists($table){
    $prefix=tableprefix();
    if (substr($table,0,strlen($prefix))!=$prefix) {
        $table= $prefix.$table;
    }
    $res = Db::query('SHOW TABLES LIKE '."'".$table."'");
    if($res){
        return true;
    }else{
        return false;
    }
}

function pdo_fieldexists($table,$column){
    $prefix=tableprefix();
    if (substr($table,0,strlen($prefix))!=$prefix) {
        $table= $prefix.$table;
    }
    $res = Db::query('select count(*) from information_schema.columns where table_name = '."'".$table."' ". 'and column_name ='."'".$column."'");
    if($res[0]['count(*)'] != 0){
        return true;
    }else{
        return false;
    }
}


<?php

/*
 * @Author: SonLight Tech
 * @Date: 2023-05-16 15:31:11
 * @LastEditors: light
 * @LastEditTime: 2023-07-09 14:01:48
 * @Description: SonLight Tech版权所有
 */


declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

// 与thinkphp6冲突的函数，需要提前预定义

function url($segment,$params=[]){

    $url='./index.php?';

    // 加入i参数
    global $_W;
    if(!empty($_W['uniacid'])){
        $url.='i='.$_W['uniacid'].'&';
    }

    $seg=explode('/',$segment);
    $a=['c','a','do'];
    if(!empty($seg)){
       foreach($seg as $k=>$v){
            $url.=$a[$k].'='.$v.'&';
       }
    }
    if(!empty($params)){
        foreach($params as $key=>$val){
             $url.=$key.'='.$val.'&';
        }
    }
    if(substr($url,-1)=='&'){
        $url=substr($url,0,-1);
    }
    return $url;
}
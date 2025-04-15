<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-09-01 14:36:04
 * @LastEditors: light
 * @LastEditTime: 2023-09-01 14:38:16
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\api;

defined('SUN_IN') or exit('Sunphp Access Denied');

class SunWxapi{

    // 微信服务器GET验证
    public static function checkSignature($token='')
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if ($tmpStr == $signature ) {
            return true;
        } else {
            return false;
        }
    }




}
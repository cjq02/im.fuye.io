<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-09-01 11:59:49
 * @LastEditors: light
 * @LastEditTime: 2023-09-01 12:02:52
 * @Description: SonLight Tech版权所有
 */

// 微信服务器api验证
function sunphp_checkSignature($token='')
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
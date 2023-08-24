<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-20 11:44:58
 * @LastEditors: light
 * @LastEditTime: 2023-08-24 15:59:24
 * @Description: SonLight Tech版权所有
 */
declare(strict_types=1);
defined('SUN_IN') or exit('Sunphp Access Denied');




if (!pdo_tableexists('sun_core_member')) {
    pdo_query("CREATE TABLE `sun_core_member` (
        `uid` int(11) NOT NULL AUTO_INCREMENT,
        `uniacid` int(11) NOT NULL,
        `openid` varchar(50) NOT NULL,
        `unionid` varchar(50) DEFAULT NULL,
        `nickname` varchar(50) DEFAULT NULL,
        `avatar` varchar(255) DEFAULT NULL,
        `password` varchar(255) DEFAULT NULL,
        `salt` varchar(50) DEFAULT NULL,
        `mobile` varchar(50) DEFAULT NULL COMMENT '手机号',
        `email` varchar(50) DEFAULT NULL,
        `credit1` decimal(10,2) DEFAULT NULL COMMENT '积分',
        `credit2` decimal(10,2) DEFAULT NULL COMMENT '余额',
        `credit3` decimal(10,2) DEFAULT NULL COMMENT '自定义',
        `credit4` decimal(10,2) DEFAULT NULL COMMENT '自定义',
        `credit5` decimal(10,2) DEFAULT NULL COMMENT '自定义',
        `credit6` decimal(10,2) DEFAULT NULL COMMENT '自定义',
        `realname` varchar(50) DEFAULT NULL COMMENT '真是姓名',
        `idcard` varchar(30) DEFAULT NULL COMMENT '身份证号码',
        `gender` tinyint(1) DEFAULT NULL COMMENT '1男；2女',
        `birthday` datetime DEFAULT NULL COMMENT '生日',
        `address` varchar(255) DEFAULT NULL COMMENT '地址',
        `alipay` varchar(50) DEFAULT NULL COMMENT '支付宝',
        `wechat` varchar(50) DEFAULT NULL COMMENT '微信号',
        `qq` varchar(50) DEFAULT NULL COMMENT 'QQ号',
        `create_time` datetime DEFAULT NULL,
        `update_time` datetime DEFAULT NULL,
        PRIMARY KEY (`uid`),
        KEY `index_o` (`openid`) USING HASH,
        KEY `index_m` (`mobile`) USING BTREE,
        KEY `index_e` (`email`) USING HASH,
        KEY `index_u` (`unionid`) USING HASH
      ) ENGINE=InnoDB AUTO_INCREMENT = 1 DEFAULT CHARSET=utf8mb4;
  ");
}


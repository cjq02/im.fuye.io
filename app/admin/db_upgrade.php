<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-20 11:44:58
 * @LastEditors: light
 * @LastEditTime: 2024-12-10 14:29:49
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
        `realname` varchar(50) DEFAULT NULL COMMENT '真实姓名',
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


if (!pdo_fieldexists('sun_core_account', 'api_url')) {
  pdo_query("ALTER TABLE `sun_core_account`
  ADD COLUMN `api_url` varchar(255) NULL COMMENT '消息推送url' AFTER `remark`,
  ADD COLUMN `api_token` varchar(255) NULL COMMENT '消息推送token' AFTER `api_url`,
  ADD COLUMN `api_key` varchar(255) NULL COMMENT '消息加密密钥' AFTER `api_token`;
  ");
}


if (!pdo_fieldexists('sun_core_account', 'wx_menu')) {
  pdo_query("ALTER TABLE `sun_core_account`
  ADD COLUMN `wx_menu` text NULL COMMENT '微信公众号菜单' AFTER `api_key`;
  ");
}


if (!pdo_tableexists('sun_core_cache')) {
  pdo_query("CREATE TABLE `sun_core_cache` (
      `key` varchar(100) NOT NULL,
      `value` text,
      PRIMARY KEY (`key`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
");
}

// 1.3.5
if (!pdo_fieldexists('sun_core_system', 'record_security')) {
  pdo_query("ALTER TABLE `sun_core_system`
  ADD COLUMN `record_security` varchar(255) NULL COMMENT '公安备案号' AFTER `record_no`;
  ");
}

// 1.4.5
if (!pdo_fieldexists('sun_core_storage', 'censor')) {
  pdo_query("ALTER TABLE `sun_core_storage`
  ADD COLUMN `censor` text NULL COMMENT '内容安全配置' AFTER `qiniu`;
  ");
}



// 1.5.7
if (!pdo_tableexists('sun_core_attachment')) {
  pdo_query("CREATE TABLE `sun_core_attachment` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `uniacid` int(11) unsigned NOT NULL,
      `uid` int(11) unsigned NOT NULL,
      `filename` varchar(255) NOT NULL,
      `attachment` varchar(255) NOT NULL,
      `type` tinyint(1) unsigned NOT NULL,
      `createtime` int(11) unsigned NOT NULL,
      `module_upload_dir` varchar(100) DEFAULT NULL,
      `group_id` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM AUTO_INCREMENT = 1  DEFAULT CHARSET=utf8;
");
}



if (!pdo_tableexists('sun_core_attachgroup')) {
  pdo_query("CREATE TABLE `sun_core_attachgroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `uniacid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
");
}


if (!pdo_tableexists('sun_core_token')) {
  pdo_query("CREATE TABLE `sun_core_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acid` int(11) NOT NULL COMMENT '平台id',
  `openid` varchar(50) NOT NULL COMMENT '公众号用户openid',
  `access_token` varchar(255) NOT NULL,
  `access_expires` datetime NOT NULL COMMENT 'access有效期',
  `refresh_token` varchar(255) NOT NULL,
  `refresh_expires` datetime NOT NULL COMMENT 'refresh有效期1月',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_oa` (`openid`,`acid`) USING HASH
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
");
}


if (!pdo_fieldexists('sun_core_system', 'img_web')) {
  pdo_query("ALTER TABLE `sun_core_system`
  ADD COLUMN `img_web` varchar(255) NULL COMMENT '电脑端登录背景图' AFTER `bind_phone`,
  ADD COLUMN `img_mobile` varchar(255) NULL COMMENT '手机端登录背景图' AFTER `img_web`;
  ");
}


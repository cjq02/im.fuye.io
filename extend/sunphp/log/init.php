<?php
/*
 * @Author: SonLight Tech
 * @Date: 2024-05-26 17:20:00
 * @LastEditors: light
 * @LastEditTime: 2024-05-26 17:20:00
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

// 加载日志函数
require_once __DIR__ . '/../function/log.php';

// 设置默认日志路径
sun_log_set_path(root_path() . 'runtime/logs/');

// 设置默认日志级别
sun_log_set_level('debug');

// 根据环境变量设置是否记录请求信息
if (defined('APP_DEBUG') && APP_DEBUG) {
    sun_log_set_record_request(true);
} 
<?php
/*
 * @Author: SonLight Tech
 * @Date: 2024-05-26 16:35:00
 * @LastEditors: light
 * @LastEditTime: 2024-05-26 16:35:00
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

use sunphp\log\SunLog;

/**
 * 全局日志记录函数
 * @param mixed $message 日志内容
 * @param string $level 日志级别
 * @param string $category 日志分类
 * @param string $file 日志文件名
 * @return bool
 */
function sun_log($message, $level = SunLog::LEVEL_INFO, $category = '', $file = '')
{
    return SunLog::write($message, $level, $category, $file);
}

/**
 * 调试日志
 */
function sun_log_debug($message, $category = '', $file = '')
{
    return SunLog::debug($message, $category, $file);
}

/**
 * 信息日志
 */
function sun_log_info($message, $category = '', $file = '')
{
    return SunLog::info($message, $category, $file);
}

/**
 * 警告日志
 */
function sun_log_warn($message, $category = '', $file = '')
{
    return SunLog::warn($message, $category, $file);
}

/**
 * 错误日志
 */
function sun_log_error($message, $category = '', $file = '')
{
    return SunLog::error($message, $category, $file);
}

/**
 * 致命错误日志
 */
function sun_log_fatal($message, $category = '', $file = '')
{
    return SunLog::fatal($message, $category, $file);
}

/**
 * 记录异常信息
 */
function sun_log_exception(\Throwable $e, $category = '', $file = '')
{
    return SunLog::exception($e, $category, $file);
}

/**
 * 快速记录SQL日志
 */
function sun_log_sql($sql, $params = [], $file = 'sql.log')
{
    $message = $sql;
    if (!empty($params)) {
        $message .= ' [params: ' . json_encode($params, JSON_UNESCAPED_UNICODE) . ']';
    }
    return SunLog::write($message, SunLog::LEVEL_INFO, 'SQL', $file);
}

/**
 * 快速记录API请求日志
 */
function sun_log_api($api, $params = [], $response = null, $file = 'api.log')
{
    $message = 'API: ' . $api;
    
    if (!empty($params)) {
        $message .= ' [request: ' . json_encode($params, JSON_UNESCAPED_UNICODE) . ']';
    }
    
    if ($response !== null) {
        $message .= ' [response: ' . json_encode($response, JSON_UNESCAPED_UNICODE) . ']';
    }
    
    return SunLog::write($message, SunLog::LEVEL_INFO, 'API', $file);
}

/**
 * 设置日志路径
 */
function sun_log_set_path($path)
{
    return SunLog::setLogPath($path);
}

/**
 * 设置日志文件名
 */
function sun_log_set_file($filename)
{
    return SunLog::setLogFile($filename);
}

/**
 * 设置日志级别
 */
function sun_log_set_level($level)
{
    return SunLog::setLogLevel($level);
}

/**
 * 设置是否显示日期
 */
function sun_log_set_show_date($show)
{
    return SunLog::setShowDate($show);
}

/**
 * 设置是否记录请求信息
 */
function sun_log_set_record_request($record)
{
    return SunLog::setRecordRequest($record);
} 
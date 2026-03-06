<?php
/*
 * @Author: SonLight Tech
 * @Date: 2024-05-26 16:30:00
 * @LastEditors: light
 * @LastEditTime: 2024-05-26 16:30:00
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\log;

defined('SUN_IN') or exit('Sunphp Access Denied');

class SunLog
{
    // 日志级别
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARN = 'warn';
    const LEVEL_ERROR = 'error';
    const LEVEL_FATAL = 'fatal';

    // 日志级别配置
    protected static $logLevels = [
        self::LEVEL_DEBUG => 0,
        self::LEVEL_INFO => 1,
        self::LEVEL_WARN => 2,
        self::LEVEL_ERROR => 3,
        self::LEVEL_FATAL => 4
    ];

    // 日志文件路径
    protected static $logPath = '';
    
    // 默认日志文件名
    protected static $logFile = 'sunphp.log';
    
    // 默认日志级别
    protected static $logLevel = self::LEVEL_INFO;
    
    // 是否显示日期
    protected static $showDate = true;
    
    // 是否记录请求信息
    protected static $recordRequest = false;
    
    // 单例实例
    private static $instance;

    /**
     * 获取实例
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
            // 设置默认日志路径
            if (empty(self::$logPath)) {
                self::$logPath = root_path() . 'runtime/logs/';
            }
            
            // 确保日志目录存在
            if (!is_dir(self::$logPath)) {
                mkdir(self::$logPath, 0777, true);
            }
        }
        return self::$instance;
    }

    /**
     * 设置日志路径
     */
    public static function setLogPath($path)
    {
        self::$logPath = rtrim($path, '/') . '/';
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0777, true);
        }
    }

    /**
     * 设置日志文件名
     */
    public static function setLogFile($filename)
    {
        self::$logFile = $filename;
    }

    /**
     * 设置日志级别
     */
    public static function setLogLevel($level)
    {
        if (isset(self::$logLevels[$level])) {
            self::$logLevel = $level;
        }
    }

    /**
     * 设置是否显示日期
     */
    public static function setShowDate($show)
    {
        self::$showDate = (bool) $show;
    }

    /**
     * 设置是否记录请求信息
     */
    public static function setRecordRequest($record)
    {
        self::$recordRequest = (bool) $record;
    }

    /**
     * 写入日志
     * @param mixed $message 日志内容
     * @param string $level 日志级别
     * @param string $category 日志分类
     * @param string $file 日志文件名
     * @return bool
     */
    public static function write($message, $level = self::LEVEL_INFO, $category = '', $file = '')
    {
        $instance = self::getInstance();
        
        // 检查日志级别
        if (self::$logLevels[$level] < self::$logLevels[self::$logLevel]) {
            return false;
        }

        // 如果没有指定日志文件名，使用默认的
        if (empty($file)) {
            $file = self::$logFile;
        }
        
        // 如果是数组或对象，转换为字符串
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        
        // 构建日志内容
        $content = '';
        
        // 添加日期
        if (self::$showDate) {
            $content .= '[' . date('Y-m-d H:i:s') . '] ';
        }
        
        // 添加日志级别
        $content .= '[' . strtoupper($level) . '] ';
        
        // 添加分类
        if (!empty($category)) {
            $content .= '[' . $category . '] ';
        }
        
        // 添加消息内容
        $content .= $message;
        
        // 添加请求信息
        if (self::$recordRequest && function_exists('request')) {
            $request = request();
            $content .= ' [URL: ' . $request->url(true) . ']';
            $content .= ' [IP: ' . $request->ip() . ']';
        }
        
        // 添加换行符
        $content .= PHP_EOL;
        
        // 写入日志
        return error_log($content, 3, self::$logPath . $file);
    }
    
    /**
     * 调试日志
     */
    public static function debug($message, $category = '', $file = '')
    {
        return self::write($message, self::LEVEL_DEBUG, $category, $file);
    }
    
    /**
     * 信息日志
     */
    public static function info($message, $category = '', $file = '')
    {
        return self::write($message, self::LEVEL_INFO, $category, $file);
    }
    
    /**
     * 警告日志
     */
    public static function warn($message, $category = '', $file = '')
    {
        return self::write($message, self::LEVEL_WARN, $category, $file);
    }
    
    /**
     * 错误日志
     */
    public static function error($message, $category = '', $file = '')
    {
        return self::write($message, self::LEVEL_ERROR, $category, $file);
    }
    
    /**
     * 致命错误日志
     */
    public static function fatal($message, $category = '', $file = '')
    {
        return self::write($message, self::LEVEL_FATAL, $category, $file);
    }
    
    /**
     * 记录异常信息
     */
    public static function exception(\Throwable $e, $category = '', $file = '')
    {
        $message = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
        $message .= "\nStack trace:\n" . $e->getTraceAsString();
        
        return self::error($message, $category, $file);
    }
} 
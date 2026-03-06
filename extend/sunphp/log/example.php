<?php
/*
 * @Author: SonLight Tech
 * @Date: 2024-05-26 16:40:00
 * @LastEditors: light
 * @LastEditTime: 2024-05-26 16:40:00
 * @Description: SunLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

// 示例文件：展示如何使用全局日志功能
// 注意：本文件仅作为使用示例，不需要实际运行

// 引入日志函数（通常在框架启动时已自动加载）
// include_once __DIR__ . '/../function/log.php';

// 设置日志路径
sun_log_set_path(root_path() . 'runtime/logs/');

// 设置默认日志文件名
sun_log_set_file('application.log');

// 设置日志级别（debug, info, warn, error, fatal）
sun_log_set_level('debug');

// 启用记录请求信息
sun_log_set_record_request(true);

// 记录不同级别的日志
sun_log_debug('这是一条调试日志', 'SYSTEM');
sun_log_info('这是一条信息日志', 'SYSTEM');
sun_log_warn('这是一条警告日志', 'SYSTEM');
sun_log_error('这是一条错误日志', 'SYSTEM');
sun_log_fatal('这是一条致命错误日志', 'SYSTEM');

// 记录数组或对象
$data = [
    'id' => 1,
    'name' => '测试用户',
    'status' => true
];
sun_log_info($data, 'DATA');

// 记录到特定的日志文件
sun_log_info('特定文件的日志信息', 'USER', 'user.log');

// 记录SQL查询
$sql = "SELECT * FROM users WHERE id = ?";
$params = [1];
sun_log_sql($sql, $params);

// 记录API请求
$api = 'https://api.example.com/users';
$request_params = ['page' => 1, 'size' => 10];
$response = ['code' => 200, 'message' => 'success', 'data' => []];
sun_log_api($api, $request_params, $response);

// 记录异常
try {
    // 模拟异常
    throw new Exception('测试异常');
} catch (Exception $e) {
    sun_log_exception($e, 'EXCEPTION');
}

// 使用通用日志函数
sun_log('通用日志信息', 'info', 'CUSTOM', 'custom.log');

// 在控制器中使用示例
class ExampleController
{
    public function index()
    {
        try {
            // 业务逻辑...
            sun_log_info('进入首页控制器', 'CONTROLLER');
            
            // 模拟数据库查询
            $result = $this->getUsers();
            sun_log_debug('查询用户结果：' . count($result) . '条记录', 'CONTROLLER');
            
            return json(['code' => 0, 'message' => '操作成功', 'data' => $result]);
        } catch (Exception $e) {
            sun_log_exception($e, 'CONTROLLER');
            return json(['code' => 1, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    private function getUsers()
    {
        // 模拟从数据库获取用户
        $sql = "SELECT * FROM users LIMIT 10";
        sun_log_sql($sql);
        
        // 模拟返回结果
        return [
            ['id' => 1, 'name' => '用户1'],
            ['id' => 2, 'name' => '用户2']
        ];
    }
} 
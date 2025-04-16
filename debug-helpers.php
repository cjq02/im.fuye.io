<?php
/**
 * Xdebug调试辅助函数
 * 在任何需要调试的文件中引入此文件，即可使用下面的函数
 */

/**
 * 在日志文件中记录调试信息
 * @param mixed $data 要记录的数据
 * @param string $label 数据标签
 * @return void
 */
function debug_log($data, $label = '') {
    $log_dir = __DIR__ . '/runtime/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_file = $log_dir . '/debug_' . date('Y-m-d') . '.log';
    $time = date('Y-m-d H:i:s');
    
    $formatted_data = print_r($data, true);
    $label = $label ? "[$label]" : '';
    
    file_put_contents(
        $log_file, 
        "[$time]$label: $formatted_data\n\n", 
        FILE_APPEND
    );
}

/**
 * 在任何地方触发断点
 * 当Xdebug连接正常时，这将触发一个断点
 * @return void
 */
function trigger_debug() {
    if (extension_loaded('xdebug')) {
        xdebug_break();
    }
}

/**
 * 打印变量并继续执行（调试辅助）
 * @param mixed $var 要打印的变量
 * @param bool $exit 是否在打印后退出
 * @return void
 */
function debug_print($var, $exit = false) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    
    if ($exit) {
        exit;
    }
}

/**
 * 记录请求信息到日志（调试API辅助）
 * @return void
 */
function log_request() {
    $data = [
        'time' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'get' => $_GET,
        'post' => $_POST,
        'headers' => getallheaders(),
        'session' => isset($_SESSION) ? $_SESSION : [],
    ];
    
    debug_log($data, 'REQUEST');
} 
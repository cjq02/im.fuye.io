<?php
/**
 * Xdebug测试文件 - 强制触发断点
 */

// 设置UTF-8编码
header('Content-Type: text/html; charset=utf-8');

// 强制触发Xdebug断点
if (function_exists('xdebug_break')) {
    echo "<!-- 正在尝试触发Xdebug断点 -->";
    xdebug_break();
}

// 显示基本信息
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Xdebug测试页面</title>
    <style>
        body { font-family: 'Microsoft YaHei', Arial, sans-serif; margin: 20px; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Xdebug测试页面</h1>
    
    <div class='box'>
        <h2>断点测试区域</h2>
        <p>这段代码应该触发一个断点：</p>
        <pre>if (function_exists('xdebug_break')) {
    xdebug_break();
}</pre>";

// 执行一些简单操作用于调试
$test_var = "测试变量";
for ($i = 0; $i < 3; $i++) {
    $test_var .= " - " . $i;
    echo "<p>循环 $i: $test_var</p>";
}

// 显示Xdebug状态
echo "<div class='box'>";
echo "<h2>Xdebug状态</h2>";
if (extension_loaded('xdebug')) {
    echo "<p class='success'>Xdebug已安装，版本：" . phpversion('xdebug') . "</p>";
    
    // 显示Xdebug设置
    echo "<h3>Xdebug配置：</h3><pre>";
    $configs = [
        'xdebug.mode',
        'xdebug.client_host',
        'xdebug.client_port',
        'xdebug.start_with_request',
        'xdebug.idekey'
    ];
    
    foreach ($configs as $config) {
        echo "$config = " . (ini_get($config) ?: '未设置') . "\n";
    }
    echo "</pre>";
} else {
    echo "<p class='warning'>Xdebug未安装或未启用</p>";
}
echo "</div>";

// 显示请求信息
echo "<div class='box'>";
echo "<h2>请求信息</h2>";
echo "<pre>";
echo "SERVER: \n";
print_r($_SERVER);
echo "\n\nGET: \n";
print_r($_GET);
echo "</pre>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>如何确保断点触发</h2>";
echo "<ol>";
echo "<li>确保在URL中添加<code>XDEBUG_SESSION_START=CURSOR</code>参数</li>";
echo "<li>或者在浏览器中设置Cookie: <code>XDEBUG_SESSION=CURSOR</code></li>";
echo "<li>确保Cursor IDE已启动调试监听器(端口9876)</li>";
echo "<li>如果断点仍未触发，请检查Docker容器中的Xdebug日志</li>";
echo "</ol>";
echo "<p>测试链接: <a href='add_xdebug_test.php?XDEBUG_SESSION_START=CURSOR' target='_blank'>添加XDEBUG_SESSION_START参数</a></p>";
echo "</div>";

echo "</body></html>"; 
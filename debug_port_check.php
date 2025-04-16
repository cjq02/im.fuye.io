<?php
/**
 * Xdebug端口检查工具
 * 这个文件能够显示PHP容器中的Xdebug配置，帮助找出端口不一致的问题
 */

// 设置UTF-8编码
header('Content-Type: text/html; charset=utf-8');

// 显示基本信息
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Xdebug端口检查</title>
    <style>
        body { font-family: 'Microsoft YaHei', Arial, sans-serif; margin: 20px; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Xdebug端口检查工具</h1>";

// 检查Xdebug是否安装
echo "<div class='box'>";
echo "<h2>Xdebug状态</h2>";
if (!extension_loaded('xdebug')) {
    echo "<p class='error'>Xdebug未安装或未启用！</p>";
    echo "</div>";
    echo "<div class='box'>";
    echo "<h2>解决方案</h2>";
    echo "<p>请确保在Docker的PHP容器中正确安装了Xdebug，可以执行以下命令：</p>";
    echo "<pre>docker-compose exec php bash -c \"php -m | grep xdebug\"</pre>";
    echo "<p>如果没有输出，说明Xdebug未安装，需要修改docker-compose.yml添加Xdebug安装命令。</p>";
    echo "</div>";
} else {
    $xdebug_version = phpversion('xdebug');
    echo "<p class='success'>Xdebug已安装，版本: $xdebug_version</p>";
    
    // 获取当前端口配置
    $client_port = ini_get('xdebug.client_port');
    
    echo "<h3>Xdebug端口配置</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>配置项</th><th>当前值</th><th>状态</th></tr>";
    
    // 检查客户端端口
    echo "<tr>";
    echo "<td>xdebug.client_port</td>";
    echo "<td>$client_port</td>";
    if ($client_port == '9876') {
        echo "<td class='success'>正确</td>";
    } else {
        echo "<td class='error'>错误（应为9876）</td>";
    }
    echo "</tr>";
    
    // 检查客户端主机
    $client_host = ini_get('xdebug.client_host');
    echo "<tr>";
    echo "<td>xdebug.client_host</td>";
    echo "<td>$client_host</td>";
    if ($client_host == 'host.docker.internal') {
        echo "<td class='success'>正确</td>";
    } else {
        echo "<td class='error'>警告（通常应为host.docker.internal）</td>";
    }
    echo "</tr>";
    
    // 检查调试模式
    $mode = ini_get('xdebug.mode');
    echo "<tr>";
    echo "<td>xdebug.mode</td>";
    echo "<td>$mode</td>";
    if (strpos($mode, 'debug') !== false) {
        echo "<td class='success'>正确</td>";
    } else {
        echo "<td class='error'>错误（应包含debug）</td>";
    }
    echo "</tr>";
    
    // 检查启动方式
    $start_with_request = ini_get('xdebug.start_with_request');
    echo "<tr>";
    echo "<td>xdebug.start_with_request</td>";
    echo "<td>$start_with_request</td>";
    if ($start_with_request == 'yes' || $start_with_request == 'trigger') {
        echo "<td class='success'>正确</td>";
    } else {
        echo "<td class='error'>错误（应为yes或trigger）</td>";
    }
    echo "</tr>";
    
    // 检查IDE Key
    $idekey = ini_get('xdebug.idekey');
    echo "<tr>";
    echo "<td>xdebug.idekey</td>";
    echo "<td>$idekey</td>";
    if ($idekey == 'CURSOR') {
        echo "<td class='success'>正确</td>";
    } else {
        echo "<td class='error'>警告（通常应为CURSOR）</td>";
    }
    echo "</tr>";
    
    echo "</table>";
    
    echo "<h3>端口连接测试</h3>";
    $can_connect = false;
    $error_msg = '';
    
    // 测试端口连接
    try {
        $socket = @fsockopen('host.docker.internal', 9876, $errno, $errstr, 1);
        if ($socket) {
            $can_connect = true;
            fclose($socket);
        } else {
            $error_msg = "$errstr ($errno)";
        }
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
    
    if ($can_connect) {
        echo "<p class='success'>成功连接到IDE调试器（host.docker.internal:9876）！</p>";
    } else {
        echo "<p class='error'>无法连接到IDE调试器：$error_msg</p>";
        echo "<p>可能原因：</p>";
        echo "<ul>";
        echo "<li>IDE未启动调试监听器</li>";
        echo "<li>Windows防火墙阻止了连接</li>";
        echo "<li>Docker容器与宿主机之间的网络配置问题</li>";
        echo "</ul>";
    }
    
    echo "</div>";
    
    // 配置修复建议
    echo "<div class='box'>";
    echo "<h2>配置不一致解决方案</h2>";
    
    if ($client_port != '9876') {
        echo "<h3>修复端口不一致问题</h3>";
        echo "<p>您的Xdebug客户端端口配置为 <strong>$client_port</strong>，而不是预期的 <strong>9876</strong>。请执行以下操作：</p>";
        echo "<ol>";
        echo "<li>修改docker-compose.yml文件中的xdebug.client_port设置为9876</li>";
        echo "<li>确保.vscode/launch.json中的port设置也是9876</li>";
        echo "<li>重启Docker容器：<pre>docker-compose down && docker-compose up -d --build</pre></li>";
        echo "</ol>";
    }
    
    if ($mode == '' || strpos($mode, 'debug') === false) {
        echo "<h3>修复调试模式问题</h3>";
        echo "<p>您的Xdebug模式设置不包含'debug'。请修改docker-compose.yml文件，添加：</p>";
        echo "<pre>echo 'xdebug.mode=debug' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini</pre>";
    }
    
    echo "<h3>测试断点触发</h3>";
    echo "<p>尝试使用以下链接测试断点触发：</p>";
    echo "<a href='add_xdebug_test.php?XDEBUG_SESSION_START=CURSOR' target='_blank'>运行测试脚本</a>";
    
    echo "</div>";
}

// 显示系统信息
echo "<div class='box'>";
echo "<h2>系统信息</h2>";
echo "<pre>";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "服务器软件: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "主机名: " . gethostname() . "\n";
echo "</pre>";
echo "</div>";

echo "</body></html>"; 
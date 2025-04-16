<?php
// 检查Xdebug和调试环境状态的小工具 - UTF-8编码版本

// 明确设置响应头为UTF-8
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 输出基本HTML头部
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Xdebug调试环境检测</title>
    <style>
        body { font-family: 'Microsoft YaHei', Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; }
        .section { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .status-ok { color: green; font-weight: bold; }
        .status-warning { color: orange; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; border-radius: 3px; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Xdebug调试环境检测工具 (UTF-8版)</h1>";

// 系统信息
echo "<div class='section'>
    <h2>系统信息</h2>
    <table>
        <tr><th>PHP版本</th><td>" . PHP_VERSION . "</td></tr>
        <tr><th>操作系统</th><td>" . PHP_OS . "</td></tr>
        <tr><th>服务器软件</th><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>
        <tr><th>服务器名称</th><td>" . $_SERVER['SERVER_NAME'] . "</td></tr>
        <tr><th>请求时间</th><td>" . date('Y-m-d H:i:s') . "</td></tr>
        <tr><th>字符编码</th><td>" . ini_get('default_charset') . "</td></tr>
    </table>
</div>";

// Xdebug检测
echo "<div class='section'>
    <h2>Xdebug状态</h2>";

if (!extension_loaded('xdebug')) {
    echo "<p class='status-error'>未检测到Xdebug扩展！</p>
        <p>可能原因：</p>
        <ul>
            <li>Xdebug未安装</li>
            <li>Xdebug安装但未启用</li>
            <li>PHP配置文件中未正确加载Xdebug</li>
        </ul>";
} else {
    echo "<p class='status-ok'>Xdebug已安装！</p>
        <p>版本: " . phpversion('xdebug') . "</p>";
    
    // 获取Xdebug配置
    echo "<h3>Xdebug配置</h3>
        <table>
            <tr><th>配置项</th><th>值</th></tr>";
    
    $xdebug_configs = [
        'xdebug.mode',
        'xdebug.start_with_request',
        'xdebug.client_host',
        'xdebug.client_port',
        'xdebug.idekey',
        'xdebug.log'
    ];
    
    foreach ($xdebug_configs as $config) {
        $value = ini_get($config);
        $status_class = empty($value) ? 'status-warning' : 'status-ok';
        echo "<tr><td>$config</td><td class='$status_class'>" . (empty($value) ? '未设置' : $value) . "</td></tr>";
    }
    
    echo "</table>";
    
    // 测试端口连接
    $port = ini_get('xdebug.client_port') ?: '9876';
    $host = ini_get('xdebug.client_host') ?: 'localhost';
    
    echo "<h3>端口测试</h3>";
    
    $socket = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($socket) {
        echo "<p class='status-ok'>成功连接到 $host:$port！调试客户端正在监听。</p>";
        fclose($socket);
    } else {
        echo "<p class='status-error'>无法连接到 $host:$port！错误：$errstr ($errno)</p>
            <p>可能原因：</p>
            <ul>
                <li>IDE调试器未启动</li>
                <li>端口被占用或无访问权限</li>
                <li>防火墙阻止了连接</li>
                <li>主机名称解析错误</li>
            </ul>";
    }
}

// 环境变量
echo "<div class='section'>
    <h2>环境变量</h2>
    <pre>";
$env_vars = [
    'PHP_IDE_CONFIG',
    'XDEBUG_CONFIG',
    'XDEBUG_SESSION'
];

foreach ($env_vars as $var) {
    echo "$var: " . (getenv($var) ?: '未设置') . "\n";
}
echo "</pre>
</div>";

// Docker容器信息
echo "<div class='section'>
    <h2>Docker环境检查</h2>";

if (file_exists('/.dockerenv')) {
    echo "<p class='status-ok'>当前在Docker容器内运行</p>";
    // 尝试获取Docker容器信息
    $container_id = @file_get_contents('/proc/self/cgroup');
    echo "<pre>容器ID信息：\n" . ($container_id ?: "无法获取") . "</pre>";
} else {
    echo "<p class='status-warning'>当前不在Docker容器内运行</p>";
}

echo "</div>";

// 提供Windows特定解决方案
echo "<div class='section'>
    <h2>Windows系统解决方案</h2>
    <p>如果遇到 <code>listen EACCES: permission denied</code> 错误：</p>
    <ol>
        <li>尝试以管理员身份运行Cursor IDE</li>
        <li>检查端口9876是否被占用: <code>netstat -ano | findstr :9876</code></li>
        <li>确保Windows防火墙允许Cursor通过所选端口通信</li>
        <li>尝试使用不同的端口号（修改launch.json和docker-compose.yml）</li>
        <li>检查IDE是否已安装PHP调试扩展</li>
    </ol>
</div>";

// 简单连接测试
echo "<div class='section'>
    <h2>测试连接</h2>
    <p>点击下面的按钮尝试触发一个断点（如果您的IDE已经在监听）：</p>
    <button onclick='triggerBreakpoint()'>触发断点</button>
    <script>
        function triggerBreakpoint() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'debug-check-utf8.php?XDEBUG_SESSION_START=CURSOR', true);
            xhr.onload = function() {
                alert('请求已发送，请检查IDE是否捕获了断点。');
            };
            xhr.send();
        }
    </script>
</div>";

// 编码测试
echo "<div class='section'>
    <h2>中文编码测试</h2>
    <p>以下是一些中文字符，用于测试编码是否正确：</p>
    <p>测试文本1：你好，世界！这是一个测试。</p>
    <p>测试文本2：中文编码测试 - UTF-8编码应该可以正确显示这些文字。</p>
    <p>特殊符号：【】「」『』《》￥§¥£€¢₴₽₩₺</p>
</div>";

// 如果请求中有调试会话参数，尝试触发断点
if (isset($_GET['XDEBUG_SESSION_START']) && extension_loaded('xdebug')) {
    echo "<!-- 尝试触发断点 -->";
    if (function_exists('xdebug_break')) {
        xdebug_break();
    }
}

echo "</div></body></html>"; 
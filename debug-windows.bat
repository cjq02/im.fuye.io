@echo off
REM 设置UTF-8编码和绿色文字以提高可读性
chcp 65001 >nul
color 0A

title Windows系统Xdebug调试工具

echo ======================================================
echo           Windows系统Xdebug调试工具
echo ======================================================
echo.
echo  此脚本将帮助您解决Windows系统下的Xdebug调试问题
echo  包括乱码、端口权限和路径映射等问题
echo.
echo ======================================================
echo.

:menu
echo 请选择操作:
echo.
echo  [1] 检查并修复端口权限问题
echo  [2] 重启Docker容器并应用调试设置
echo  [3] 检查Windows编码设置
echo  [4] 创建示例断点测试页面
echo  [5] 修复Windows路径映射问题
echo  [0] 退出
echo.

set /p choice=请输入选项编号: 

if "%choice%"=="1" goto check_port
if "%choice%"=="2" goto restart_docker
if "%choice%"=="3" goto check_encoding
if "%choice%"=="4" goto create_test_page
if "%choice%"=="5" goto fix_path_mapping
if "%choice%"=="0" goto end

echo 无效的选项，请重新输入!
goto menu

:check_port
cls
echo ======================================================
echo             检查并修复端口权限问题
echo ======================================================
echo.

echo 检查是否以管理员权限运行...
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [错误] 未以管理员权限运行!
    echo 请右键点击此脚本并选择"以管理员身份运行"。
    pause
    goto menu
)

echo [已通过] 脚本以管理员权限运行
echo.

echo 检查端口占用情况...
netstat -ano | findstr :9876
if %errorLevel% equ 0 (
    echo [警告] 端口9876已被占用！
    echo 尝试查找占用端口的进程...
    
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :9876') do (
        set pid=%%a
        echo 找到PID: !pid!
        echo 尝试终止进程...
        taskkill /F /PID !pid!
        if !errorLevel! equ 0 (
            echo [成功] 终止了占用端口的进程
        ) else (
            echo [失败] 无法终止进程，请手动关闭
        )
    )
) else (
    echo [已通过] 端口9876未被占用
)
echo.

echo 配置Windows防火墙规则...
netsh advfirewall firewall show rule name="Xdebug 9876" >nul 2>&1
if %errorLevel% equ 0 (
    echo 防火墙规则已存在，删除旧规则...
    netsh advfirewall firewall delete rule name="Xdebug 9876"
)

echo 创建新的防火墙规则...
netsh advfirewall firewall add rule name="Xdebug 9876" dir=in action=allow protocol=TCP localport=9876
echo [完成] 已添加防火墙入站规则允许端口9876
echo.

pause
goto menu

:restart_docker
cls
echo ======================================================
echo           重启Docker容器并应用调试设置
echo ======================================================
echo.

echo 检查Docker是否运行...
docker ps >nul 2>&1
if %errorLevel% neq 0 (
    echo [错误] Docker未运行或未安装!
    echo 请确保Docker Desktop正在运行。
    pause
    goto menu
)

echo [已通过] Docker正在运行
echo.

echo 重新启动Docker容器...
docker-compose down
echo.
echo 启动容器并应用Xdebug设置...
docker-compose up -d --build
echo.
echo [完成] Docker容器已重启
echo.

pause
goto menu

:check_encoding
cls
echo ======================================================
echo             检查Windows编码设置
echo ======================================================
echo.

echo 当前控制台代码页: 
chcp
echo.

echo 如果您在使用中文时看到乱码，请尝试以下操作:
echo.
echo 1. 确认您的控制台使用的是Unicode或UTF-8编码
echo 2. 修改Windows区域设置为中文(以管理员权限运行)
echo    控制面板 -> 区域 -> 管理 -> 更改系统区域设置 -> 中文(简体)
echo 3. 修改PowerShell/CMD的字体为支持中文的字体(如NSimSun或Consolas)
echo.
echo 点击以下命令创建一个支持中文的测试页面:
echo    http://localhost/debug-check-utf8.php
echo.

pause
goto menu

:create_test_page
cls
echo ======================================================
echo             创建示例断点测试页面
echo ======================================================
echo.

echo 创建测试文件debug-test.php...
echo ^<?php > debug-test.php
echo // UTF-8测试断点页面 >> debug-test.php
echo header('Content-Type: text/html; charset=utf-8'); >> debug-test.php
echo echo "^<!DOCTYPE html^>"; >> debug-test.php
echo echo "^<html^>"; >> debug-test.php
echo echo "^<head^>"; >> debug-test.php
echo echo "    ^<meta charset=\"UTF-8\"^>"; >> debug-test.php
echo echo "    ^<title^>Xdebug测试页面^</title^>"; >> debug-test.php
echo echo "^</head^>"; >> debug-test.php
echo echo "^<body^>"; >> debug-test.php
echo echo "    ^<h1^>Xdebug测试页面^</h1^>"; >> debug-test.php
echo echo "    ^<p^>这是一个测试PHP断点的页面，请在此处添加断点^</p^>"; >> debug-test.php
echo echo "    ^<hr^>"; >> debug-test.php
echo echo "    ^<div^>"; >> debug-test.php
echo echo "        ^<?php"; >> debug-test.php
echo echo "        // 在这里设置断点"; >> debug-test.php
echo echo "        \$test_var = '测试变量';"; >> debug-test.php
echo echo "        for (\$i = 0; \$i ^< 3; \$i++) {"; >> debug-test.php
echo echo "            \$test_var .= \" - {\$i}\";"; >> debug-test.php
echo echo "            // 这里也可以设置断点"; >> debug-test.php
echo echo "            echo \"^<p^>循环 {\$i}: {\$test_var}^</p^>\";"; >> debug-test.php
echo echo "        }"; >> debug-test.php
echo echo "        ";  >> debug-test.php
echo echo "        // 如果存在Xdebug，尝试触发断点"; >> debug-test.php
echo echo "        if (function_exists('xdebug_break') ^&^& isset(\$_GET['debug'])) {"; >> debug-test.php
echo echo "            xdebug_break();"; >> debug-test.php
echo echo "        }"; >> debug-test.php
echo echo "        ?^>"; >> debug-test.php
echo echo "    ^</div^>"; >> debug-test.php
echo echo "    ^<hr^>"; >> debug-test.php
echo echo "    ^<p^>访问此页面时添加参数 ^<code^>?debug=1^</code^> 可以触发Xdebug断点。^</p^>"; >> debug-test.php
echo echo "^</body^>"; >> debug-test.php
echo echo "^</html^>"; >> debug-test.php
echo ?^> >> debug-test.php

echo [完成] 测试文件已创建: debug-test.php
echo.
echo 要测试断点，请执行以下操作:
echo 1. 启动Cursor IDE的Xdebug监听器
echo 2. 访问 http://localhost/debug-test.php?debug=1
echo 3. 观察Cursor IDE是否捕获到断点
echo.

pause
goto menu

:fix_path_mapping
cls
echo ======================================================
echo           修复Windows路径映射问题
echo ======================================================
echo.

echo Windows系统中的路径映射问题常见于Docker环境。
echo.
echo 请确认您的launch.json文件中包含正确的路径映射:
echo     "pathMappings": {
echo         "/var/www/html": "${workspaceFolder}"
echo     }
echo.
echo 如果Windows路径包含空格或特殊字符，可能会导致映射问题。
echo 建议将项目放在没有空格的路径下，例如 D:\Projects\myapp
echo.
echo 如果您使用的是WSL2，请确保在Docker Desktop设置中启用了WSL集成。
echo.

pause
goto menu

:end
cls
echo ======================================================
echo               感谢使用调试工具
echo ======================================================
echo.
echo  如需更多帮助，请参考windows-debug-guide.md文件
echo  或访问 http://localhost/debug-check-utf8.php
echo.
echo ======================================================
echo.
echo 再见!
echo.
timeout /t 3 >nul 
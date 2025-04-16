@echo off
chcp 65001
color 0A
echo Windows Xdebug调试环境配置工具
echo =============================
echo.

REM 检查管理员权限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo 需要管理员权限运行此脚本。请右键点击并选择"以管理员身份运行"。
    pause
    exit /b 1
)

REM 检查端口占用情况
echo 检查端口占用情况...
netstat -ano | findstr :9876
if %errorLevel% equ 0 (
    echo 警告: 端口9876已被占用！请尝试结束对应的进程或使用其他端口。
    echo.
) else (
    echo 端口9876未被占用，可以正常使用。
    echo.
)

REM 配置防火墙规则
echo 配置Windows防火墙规则...
echo.
netsh advfirewall firewall show rule name="Xdebug 9876" >nul 2>&1
if %errorLevel% equ 0 (
    echo 防火墙规则已存在，删除旧规则...
    netsh advfirewall firewall delete rule name="Xdebug 9876"
)

echo 创建新的防火墙规则...
netsh advfirewall firewall add rule name="Xdebug 9876" dir=in action=allow protocol=TCP localport=9876

echo.
echo 重新启动Docker容器
echo.
docker-compose down
docker-compose up -d --build
echo.

echo 配置完成！
echo.
echo 请按照以下步骤操作：
echo 1. 重新启动Cursor IDE (以管理员身份运行)
echo 2. 启动调试监听器 (按下F5或点击Debug按钮)
echo 3. 访问测试页面: http://localhost/app/index.php?i=1^&c=entry^&do=test^&m=mdkeji_im
echo.
echo 如果调试仍然不工作，请尝试访问调试检测工具: http://localhost/debug-check.php
echo.

pause

# Windows系统下Xdebug调试指南

## 解决 `listen EACCES: permission denied` 错误

这个错误表明Cursor IDE无法在指定端口上监听，通常是Windows权限问题导致的。

### 解决方法一：使用自动修复脚本

1. 右键点击项目根目录下的`debug-command.bat`文件
2. 选择"以管理员身份运行"
3. 按照脚本提示完成配置

### 解决方法二：手动修复步骤

#### 1. 以管理员身份运行Cursor

1. 关闭所有Cursor窗口
2. 在Windows开始菜单中找到Cursor图标
3. 右键点击并选择"以管理员身份运行"
4. 重新打开项目

#### 2. 检查端口占用

1. 以管理员身份打开PowerShell或命令提示符
2. 执行以下命令查看端口占用：
   ```
   netstat -ano | findstr :9876
   ```
3. 如果有程序占用此端口，记下PID（最后一列显示的数字）
4. 打开任务管理器，在"详细信息"或"进程"选项卡中找到对应PID的程序
5. 右键点击并选择"结束任务"

#### 3. 配置Windows防火墙

1. 打开Windows防火墙高级设置（在控制面板或通过搜索"高级防火墙"）
2. 点击左侧的"入站规则"
3. 在右侧面板点击"新建规则"
4. 选择"端口"，点击"下一步"
5. 选择"TCP"，输入端口"9876"，点击"下一步"
6. 选择"允许连接"，点击"下一步"
7. 保持所有网络类型选中，点击"下一步"
8. 命名为"Xdebug调试端口9876"，点击"完成"

#### 4. 重新构建Docker容器

1. 确保Docker Desktop正在运行
2. 在项目根目录打开终端
3. 执行以下命令：
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```

## 调试技巧

### 1. 使用调试检测工具

访问 `http://localhost/debug-check.php`，这个页面会显示：
- Xdebug安装状态
- 当前配置参数
- 端口连接测试结果
- 系统环境变量

### 2. 使用浏览器扩展

1. 安装Chrome/Firefox的Xdebug扩展（如Xdebug helper）
2. 点击扩展图标启用调试
3. 访问您的测试页面：`http://localhost/app/index.php?i=1&c=entry&do=test&m=mdkeji_im`

### 3. URL参数触发

在任何URL后添加`?XDEBUG_SESSION_START=CURSOR`参数触发调试，例如：
```
http://localhost/app/index.php?i=1&c=entry&do=test&m=mdkeji_im&XDEBUG_SESSION_START=CURSOR
```

### 4. 使用命令行触发

如果需要调试命令行脚本，运行：
```bash
docker-compose exec -e XDEBUG_SESSION=CURSOR php php /var/www/html/你的脚本.php
```

## 疑难解答

### Xdebug已安装但断点不工作

1. 检查PHP容器中的Xdebug日志：
   ```bash
   docker-compose exec php cat /var/log/xdebug.log
   ```

2. 确认pathMappings路径正确：
   - 容器中的`/var/www/html`应该映射到本地项目根目录

3. 检查Xdebug模式设置：
   ```bash
   docker-compose exec php php -i | grep xdebug.mode
   ```
   应当包含`debug`

### 如果一切设置正确但仍然无法调试

1. 尝试使用不同的端口：编辑`.vscode/launch.json`和`docker-compose.yml`文件，使用更高的端口号（如12345）
2. 重新启动Docker和IDE
3. 如果在公司网络中，咨询IT部门是否有网络限制

## 在Windows系统下启动Docker容器

重新构建并启动Docker容器：

```bash
docker-compose down
docker-compose up -d --build
```

## 配置Cursor IDE

1. 确保安装了PHP Debug扩展
2. 确保launch.json配置正确（已修改为9876端口）
3. 启动调试监听器（点击运行和调试按钮）

## 测试连接

访问测试页面：`http://localhost/app/index.php?i=1&c=entry&do=test&m=mdkeji_im`

## 使用Windows特有的Docker配置

在Windows系统下使用Docker时，可能需要特别注意以下几点：

1. 确保WSL2正常工作
2. 在Docker Desktop设置中启用WSL2集成
3. 如果使用的是Hyper-V模式，可能需要额外的网络配置

## 常见问题排查

1. 如果依然无法连接，请尝试临时关闭Windows防火墙和杀毒软件
2. 检查Docker Desktop是否具有足够权限
3. 尝试使用Docker容器内的IP地址代替host.docker.internal 
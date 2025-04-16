# Xdebug触发方法

## URL方式触发

将以下参数添加到您的URL中以触发调试：

```
http://localhost/app/index.php?i=1&c=entry&do=test&m=mdkeji_im&XDEBUG_SESSION_START=CURSOR
```

## Cookie方式触发

您也可以通过设置Cookie来触发调试：

1. 在浏览器中添加Cookie:
   - 名称: `XDEBUG_SESSION`
   - 值: `CURSOR`

## 浏览器扩展方式触发

1. Chrome/Edge: 安装 [Xdebug Helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc)
2. Firefox: 安装 [Xdebug Helper](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/)
3. 设置IDE key为`CURSOR`
4. 点击扩展图标启用调试

## 在PHP代码中显式触发断点

在您需要调试的PHP文件中添加：

```php
if (function_exists('xdebug_break')) {
    xdebug_break();
}
```

## 检查断点是否触发：

1. 确保Cursor IDE的调试监听器已启动（端口9876）
2. 访问URL时添加断点触发参数
3. 查看Cursor IDE底部的调试控制台是否有连接消息
4. 如果没有，检查Xdebug日志：`docker-compose exec php cat /var/log/xdebug.log` 
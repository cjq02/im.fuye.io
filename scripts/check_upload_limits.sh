#!/bin/bash
# 在服务器上执行：检查 nginx / PHP 上传与超时配置
echo "=== nginx client_max_body_size ==="
grep -r client_max_body_size /etc/nginx/ 2>/dev/null || echo "not found"
echo ""
echo "=== PHP ini (upload/post/execution) ==="
php -i 2>/dev/null | grep -E "upload_max_filesize|post_max_size|max_execution_time" || true
echo ""
echo "=== PHP-FPM or Apache timeouts (if any) ==="
grep -r "request_terminate_timeout\|Timeout\|proxy_read_timeout\|send_timeout" /etc/nginx/ /etc/php/ 2>/dev/null | head -20 || true

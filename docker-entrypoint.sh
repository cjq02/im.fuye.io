#!/bin/bash
set -e

# 日志输出函数，输出到 stderr 以便 docker logs 能看到
log() {
    echo "$@" >&2
}

log "Starting PHP container initialization..."

# 设置权限
log "Setting file permissions..."
chown -R www-data:www-data /var/www/im.fuye.io || true
chmod -R 755 /var/www/im.fuye.io || true
chmod -R 777 /var/www/im.fuye.io/runtime 2>/dev/null || true

# 动态获取 MySQL 容器 IP 并配置，使 127.0.0.1:3306 转发到 MySQL 容器
setup_mysql_forwarding() {
    log "Setting up MySQL port forwarding..."
    max_attempts=60
    attempt=0

    while [ $attempt -lt $max_attempts ]; do
        MYSQL_IP=$(getent hosts mysql 2>/dev/null | awk '{print $1}' | head -n1)

        if [ -n "$MYSQL_IP" ] && [ "$MYSQL_IP" != "127.0.0.1" ]; then
            log "Found MySQL container at ${MYSQL_IP}, setting up port forwarding..."

            if pgrep -f "socat.*3306" >/dev/null 2>&1; then
                log "Socat forwarding already running, skipping setup"
                return 0
            fi

            if ! timeout 2 bash -c "</dev/tcp/${MYSQL_IP}/3306" 2>/dev/null; then
                log "Warning: MySQL container at ${MYSQL_IP}:3306 is not reachable yet, will retry..."
                return 1
            fi

            log "Starting socat: 127.0.0.1:3306 -> ${MYSQL_IP}:3306"
            nohup socat TCP-LISTEN:3306,bind=127.0.0.1,reuseaddr,fork,keepalive TCP:${MYSQL_IP}:3306,keepalive >/tmp/socat.log 2>&1 &
            SOCAT_PID=$!
            sleep 2

            if kill -0 $SOCAT_PID 2>/dev/null; then
                if timeout 2 bash -c "</dev/tcp/127.0.0.1/3306" 2>/dev/null; then
                    log "Successfully started MySQL forwarding: 127.0.0.1:3306 -> ${MYSQL_IP}:3306 (PID: $SOCAT_PID)"
                    return 0
                else
                    log "Warning: Socat started but port 127.0.0.1:3306 is not accessible"
                    kill $SOCAT_PID 2>/dev/null || true
                    return 1
                fi
            else
                log "Warning: Failed to start socat forwarding (process died immediately)"
                [ -f /tmp/socat.log ] && log "Socat error log: $(cat /tmp/socat.log)"
                return 1
            fi
        else
            if [ $((attempt % 10)) -eq 0 ]; then
                log "Waiting for MySQL container... (attempt $attempt/$max_attempts)"
            fi
        fi

        attempt=$((attempt + 1))
        sleep 1
    done

    log "Warning: Could not setup MySQL forwarding after ${max_attempts} attempts"
}

# 在后台设置 MySQL 转发（不阻塞主进程）
setup_mysql_forwarding &

log "PHP container initialization completed, starting main process..."

# 启动 GatewayWorker（如果存在）
if [ -f "/var/www/im.fuye.io/addons/mdkeji_im/extend/GatewayWorker/start.php" ]; then
    log "Starting GatewayWorker..."
    php /var/www/im.fuye.io/addons/mdkeji_im/extend/GatewayWorker/start.php start -d || log "GatewayWorker start failed, continuing..."
else
    log "GatewayWorker not found, skipping..."
fi

# 执行传入的命令（默认是 php-fpm）
exec "$@"

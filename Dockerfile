# 与生产环境一致：CentOS Stream 8 / Rocky Linux 8 + PHP 7.4
# 生产环境：CentOS Stream 8, PHP 7.4.33
FROM rockylinux:8

# 设置时区
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 安装 EPEL、Remi 和 PHP 7.4
RUN dnf install -y epel-release && \
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm && \
    dnf module enable -y php:7.4 && \
    dnf install -y \
        php-fpm \
        php-cli \
        php-json \
        php-mysqlnd \
        php-zip \
        php-gd \
        php-bcmath \
        php-process \
        git \
        zip \
        unzip \
        socat \
        procps-ng \
        net-tools \
    && dnf clean all

# 创建 www-data 用户（与 Debian 版保持一致，便于 entrypoint）
RUN groupadd -r www-data 2>/dev/null || true && \
    useradd -r -g www-data -s /sbin/nologin -d /var/www www-data 2>/dev/null || true

# 创建 /run/php-fpm 和 /var/log/php-fpm 并设置权限（非 root 运行需可写）
ARG BUILD_UID=1000
ARG BUILD_GID=1000
RUN mkdir -p /run/php-fpm /var/log/php-fpm && \
    chown -R ${BUILD_UID}:${BUILD_GID} /run/php-fpm /var/log/php-fpm

# 配置 PHP
RUN echo 'date.timezone = Asia/Shanghai' > /etc/php.d/99-timezone.ini && \
    echo 'max_execution_time = 300' >> /etc/php.d/99-timezone.ini && \
    echo 'upload_max_filesize = 100M' >> /etc/php.d/99-timezone.ini && \
    echo 'post_max_size = 100M' >> /etc/php.d/99-timezone.ini && \
    echo 'max_input_time = 300' >> /etc/php.d/99-timezone.ini && \
    echo 'memory_limit = 256M' >> /etc/php.d/99-timezone.ini && \
    echo 'display_errors = On' >> /etc/php.d/99-timezone.ini && \
    echo 'error_reporting = E_ALL' >> /etc/php.d/99-timezone.ini && \
    echo 'output_buffering = 0' >> /etc/php.d/99-timezone.ini && \
    echo 'implicit_flush = On' >> /etc/php.d/99-timezone.ini && \
    echo 'session.save_path = /var/www/im.fuye.io/runtime/session' >> /etc/php.d/99-timezone.ini

# PHP-FPM 配置（Docker 需监听 0.0.0.0，前台运行，允许 Nginx 容器连接，以宿主机用户运行）
RUN sed -i 's/listen = .*/listen = 0.0.0.0:9000/' /etc/php-fpm.d/www.conf && \
    sed -i 's/^listen.allowed_clients/;listen.allowed_clients/' /etc/php-fpm.d/www.conf && \
    sed -i 's/^user = .*/user = '${BUILD_UID}'/' /etc/php-fpm.d/www.conf && \
    sed -i 's/^group = .*/group = '${BUILD_GID}'/' /etc/php-fpm.d/www.conf && \
    sed -i 's/;catch_workers_output/catch_workers_output/' /etc/php-fpm.d/www.conf && \
    sed -i 's/;decorate_workers_output/decorate_workers_output/' /etc/php-fpm.d/www.conf && \
    sed -i 's/decorate_workers_output = yes/decorate_workers_output = no/' /etc/php-fpm.d/www.conf && \
    echo 'request_terminate_timeout = 300' >> /etc/php-fpm.d/www.conf && \
    echo 'php_admin_value[session.save_path] = /var/www/im.fuye.io/runtime/session' >> /etc/php-fpm.d/www.conf && \
    sed -i 's/^daemonize = yes/daemonize = no/' /etc/php-fpm.conf

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www/im.fuye.io

# 设置权限
RUN mkdir -p /var/www/im.fuye.io && chown -R www-data:www-data /var/www

# 暴露端口
EXPOSE 9000 9075

# 启动脚本
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

FROM php:7.4-fpm

# 设置时区
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 安装系统依赖
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        git \
        zip \
        unzip \
        libzip-dev \
        libpng-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        socat \
        procps \
        net-tools \
        && rm -rf /var/lib/apt/lists/*

# 配置并安装 PHP 扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        zip \
        gd \
        pcntl \
        posix \
        sockets \
        bcmath

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 配置 PHP
RUN echo 'exec=1' > /usr/local/etc/php/conf.d/docker-php-ext-exec.ini && \
    echo 'date.timezone = Asia/Shanghai' > /usr/local/etc/php/conf.d/timezone.ini && \
    echo 'disable_functions = ' > /usr/local/etc/php/conf.d/disable_functions.ini && \
    echo 'max_execution_time = 300' > /usr/local/etc/php/conf.d/timeout.ini && \
    echo 'request_terminate_timeout = 300' > /usr/local/etc/php-fpm.d/timeout.conf && \
    echo 'catch_workers_output = yes' > /usr/local/etc/php-fpm.d/log-output.conf && \
    echo 'decorate_workers_output = no' >> /usr/local/etc/php-fpm.d/log-output.conf && \
    echo 'display_errors = On' > /usr/local/etc/php/conf.d/display_errors.ini && \
    echo 'error_reporting = E_ALL' >> /usr/local/etc/php/conf.d/display_errors.ini && \
    echo 'output_buffering = 0' > /usr/local/etc/php/conf.d/output-buffering.ini && \
    echo 'implicit_flush = On' >> /usr/local/etc/php/conf.d/output-buffering.ini && \
    echo 'upload_max_filesize = 100M' > /usr/local/etc/php/conf.d/upload.ini && \
    echo 'post_max_size = 100M' >> /usr/local/etc/php/conf.d/upload.ini && \
    echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/upload.ini && \
    echo 'max_input_time = 300' >> /usr/local/etc/php/conf.d/upload.ini && \
    echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/upload.ini

# 设置工作目录
WORKDIR /var/www/im.fuye.io

# 设置权限
RUN chown -R www-data:www-data /var/www

# 暴露端口
EXPOSE 9000 9075

# 启动脚本
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

FROM php:8.3-fpm-alpine

RUN apk add --no-cache nginx supervisor \
    && docker-php-ext-install pdo_mysql \
    && mkdir -p /run/nginx /var/lib/nginx/tmp \
    && rm -f /etc/nginx/http.d/default.conf

RUN sed -i \
    -e 's|^;slowlog = .*|slowlog = /proc/self/fd/2|' \
    -e 's|^;request_slowlog_timeout = .*|request_slowlog_timeout = 10s|' \
    -e 's|^;request_terminate_timeout = .*|request_terminate_timeout = 30s|' \
    /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf
COPY . .

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8080/health.php || exit 1

CMD ["supervisord", "-c", "/etc/supervisord.conf"]

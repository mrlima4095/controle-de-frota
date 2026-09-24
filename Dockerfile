FROM php:8.3-fpm-alpine

RUN apk add --no-cache nginx supervisor \
    && mkdir -p /run/nginx /var/lib/nginx/tmp \
    && rm -f /etc/nginx/http.d/default.conf

WORKDIR /var/www/html

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf
COPY . .

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8080/ || exit 1

CMD ["supervisord", "-c", "/etc/supervisord.conf"]

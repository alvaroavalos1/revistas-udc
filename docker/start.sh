#!/bin/bash
set -e

PORT=${PORT:-80}
echo "[start] PORT=${PORT}"

sed "s|NGINX_PORT|${PORT}|g" /etc/nginx/conf.d/app.conf.template \
    > /etc/nginx/conf.d/app.conf

nginx -t
echo "[start] nginx config OK, starting services"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf

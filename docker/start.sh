#!/bin/bash
set -e

echo "[start] PORT=${PORT:-8080} (nginx hardcoded to 8080)"

nginx -t
echo "[start] nginx config OK, starting services"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf

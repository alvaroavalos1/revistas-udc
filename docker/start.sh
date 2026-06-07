#!/bin/bash
export PORT=${PORT:-80}
envsubst '${PORT}' < /etc/nginx/conf.d/app.conf.template > /etc/nginx/conf.d/app.conf
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf

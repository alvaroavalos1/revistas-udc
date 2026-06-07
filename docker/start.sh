#!/bin/bash
PORT=${PORT:-80}
sed -i "s/RAILWAY_PORT/${PORT}/g" /etc/nginx/sites-available/default
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf

#!/bin/bash
PORT=${PORT:-80}
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/RAILWAY_PORT/${PORT}/g" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground

FROM php:8.2-fpm

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN rm -f /etc/nginx/sites-enabled/default

COPY docker/nginx.conf /etc/nginx/conf.d/app.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]

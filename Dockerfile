FROM php:8.2-fpm

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor default-mysql-client unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN rm -f /etc/nginx/sites-enabled/default

COPY docker/php-sessions.ini /usr/local/etc/php/conf.d/sessions.ini
COPY docker/nginx.conf /etc/nginx/conf.d/app.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY composer.json /var/www/html/
RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www/html
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]

FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && rm -f /etc/apache2/mods-enabled/mpm_*.load \
             /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite headers

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]

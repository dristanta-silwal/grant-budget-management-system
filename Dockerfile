FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev git unzip ca-certificates \
 && docker-php-ext-install pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader

RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/api#' /etc/apache2/sites-available/000-default.conf \
 && a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
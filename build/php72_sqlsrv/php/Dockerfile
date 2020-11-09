FROM php:7.2-fpm

# install php-ext
RUN apt-get update && apt-get install -y git wget gnupg vim unzip libxml2-dev libpng-dev libzip-dev libonig-dev \
  && docker-php-ext-install mbstring dom gd zip \
  && apt-get clean


# Append ODBC Driver
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
  && curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list \
  && apt-get update \
  && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools \
  && apt-get install -y unixodbc-dev libgssapi-krb5-2


# install driver
RUN pecl install sqlsrv && pecl install pdo_sqlsrv \
  && docker-php-ext-enable sqlsrv \
  && docker-php-ext-enable pdo_sqlsrv


# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# install exment
RUN mkdir /var/www/exment
WORKDIR /var/www/exment
RUN COMPOSER_MEMORY_LIMIT=-1 composer create-project "laravel/laravel=5.6.*" . \
  && COMPOSER_MEMORY_LIMIT=-1 composer require exceedone/exment
RUN php artisan vendor:publish --provider="Exceedone\\Exment\\ExmentServiceProvider"
RUN chown -R www-data:www-data /var/www/exment \
  && chmod 755 -R /var/www/exment/storage \
  && chmod 755 -R /var/www/exment/bootstrap/cache

# remove env file. because use volumes env file
RUN rm /var/www/exment/.env


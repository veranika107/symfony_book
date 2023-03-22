FROM php:fpm

RUN apt-get -y update

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/conf.d/php.ini

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN apt install -y libxslt-dev \
    && docker-php-ext-install xsl

# Install opcache extension for PHP accelerator
RUN docker-php-ext-install opcache \
    && docker-php-ext-enable opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get -y install git

RUN apt-get -y install nano

RUN apt-get -y install graphviz

RUN apt-get install -y --no-install-recommends \
            curl \
            libmemcached-dev \
            libz-dev \
            libpq-dev \
            libjpeg-dev \
            libpng-dev \
            libfreetype6-dev \
            libssl-dev \
            libwebp-dev \
            libxpm-dev \
            libmcrypt-dev \
            libonig-dev;

RUN docker-php-ext-install gd

# Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

RUN echo 'opcache.max_accelerated_files = 20000' >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Install PHP Redis extension
RUN pecl install -o -f redis \
  && rm -rf /tmp/pear \
  && docker-php-ext-enable redis

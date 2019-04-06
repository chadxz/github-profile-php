FROM php:7.3-apache-stretch

# ------------------------------
# Composer
# ------------------------------
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /tmp
ENV COMPOSER_VERSION 1.8.4

RUN curl --silent --fail --location --retry 3 --output /tmp/installer.php \
         --url https://raw.githubusercontent.com/composer/getcomposer.org/cb19f2aa3aeaa2006c0cd69a7ef011eb31463067/web/installer && \
    php -r "\$signature = '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5'; \
            \$hash = hash('sha384', file_get_contents('/tmp/installer.php')); \
            if (!hash_equals(\$signature, \$hash)) { \
              unlink('/tmp/installer.php'); \
              echo 'Integrity check failed, installer is either corrupt or worse.' . PHP_EOL; \
              exit(1); \
            }" && \
    php /tmp/installer.php --no-ansi --install-dir=/usr/bin --filename=composer --version=${COMPOSER_VERSION} && \
    composer --ansi --version --no-interaction && \
    rm -f /tmp/installer.php

# ------------------------------
# Application
# ------------------------------
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
    apt-get update -qq && \
    DEBIAN_FRONTEND=noninteractive \
    apt-get install -y --no-install-recommends \
        git \
        libzip-dev \
        nodejs \
        unzip \
        vim \
        && \
    apt-get purge -y --auto-remove && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install json zip && \
    pecl install -o -f xdebug-2.7.0 && \
    rm -rf /tmp/pear

# Install application
COPY composer.json composer.lock package.json package-lock.json /var/www/html/
RUN composer install && npm install
COPY . /var/www/html

# ------------------------------
# Configuration
# ------------------------------
RUN ln -sfv /var/www/html/config/apache2/conf/* /etc/apache2/conf-available/ && \
    ln -sfv /var/www/html/config/apache2/sites/* /etc/apache2/sites-available/ && \
    ln -sfv /dev/stdout /var/log/apache2/access.log && \
    ln -sfv /dev/stderr /var/log/apache2/error.log && \
    ln -sfv /var/www/html/config/php/* /usr/local/etc/php/conf.d/ && \
    a2enmod rewrite && \
    a2enconf fqdn log && \
    a2dissite 000-default && \
    a2ensite 001-app

ENTRYPOINT ["/var/www/html/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
LABEL name=github-profile-php

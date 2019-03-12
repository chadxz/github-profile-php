#!/bin/bash

# properly handle SIGTERM and SIGINT
trap 'exit 0' TERM INT

# Enable Xdebug if we are in debug mode
if [ "${DEBUG}" == "true" ]; then
    echo "enabling xdebug..."
    docker-php-ext-enable xdebug
fi

# Rewrite apache ports to support Heroku weirdness
# https://stackoverflow.com/questions/54452086/deploy-a-dockerized-laravel-app-using-apache-on-heroku
if [[ "${PORT}" ]]; then
    echo "rewriting apache ports to ${PORT}..."
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/001-app.conf
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
fi

set -ex
exec docker-php-entrypoint "$@"

FROM php:latest

ADD . /var/www
WORKDIR /var/www

# Install composer
RUN apt-get update && apt-get install -y git zip cron anacron
RUN curl -s https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Restore composer
RUN composer install

#Install mysql
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

RUN echo "php /var/www/cronjobs/sessions_check.php\
php /var/www/cronjobs/sync_groups.php\
php /var/www/cronjobs/update_db.php\
php /var/www/cronjobs/user_check.php" > /etc/cron.hourly/citadel

ENV docker=true
VOLUME [ "/var/www/logs" ]
VOLUME [ "/var/www/config" ]
EXPOSE 8080

ARG WITH_XDEBUG=false

RUN if [ $WITH_XDEBUG = "true" ] ; then \
        pecl install xdebug; \
        docker-php-ext-enable xdebug; \
        echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi ;

ENTRYPOINT php -S 0.0.0.0:8080 -t public public/index.php

#docker run -v D:\Github\eve-citadel\logs:/var/www/logs -p 8080:8080 citadel
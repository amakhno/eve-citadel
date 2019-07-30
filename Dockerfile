FROM php:latest

ADD . /var/www
WORKDIR /var/www

# Install composer
RUN apt-get update && apt-get install -y git zip cron anacron
RUN curl -s https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Restore composer
RUN composer install

RUN echo "php /var/www/cronjobs/sessions_check.php\
php /var/www/cronjobs/sync_groups.php\
php /var/www/cronjobs/update_db.php\
php /var/www/cronjobs/user_check.php" > /etc/cron.hourly/citadel

ENV docker=true
VOLUME [ "/var/www/logs" ]
EXPOSE 8080

ENTRYPOINT php -S 0.0.0.0:8080 -t public public/index.php

#docker run -v D:\Github\eve-citadel\logs:/var/www/logs -p 8080:8080 citadel
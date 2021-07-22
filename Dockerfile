FROM debian:buster-slim
# Install Git, PHP, Node and NPM
WORKDIR /var/www
RUN apt-get update && apt-get install -y git cron apache2 php libapache2-mod-php php-curl curl nodejs npm
RUN rm -rf /var/www/html && mkdir /var/www/html
WORKDIR /var/www/html
# Add the application source code
ADD . .
# Build
RUN npm i && npm rebuild node-sass
ARG URL
ENV URL ${URL:-co2widget.com}
RUN chmod 777 /var/www/html/build && php server.php && node_modules/.bin/gulp

EXPOSE 80
ENTRYPOINT service cron start && echo URL=$URL && \
    echo "0 0,6,12,18 * * * (cd /var/www/html && echo 'syncing server.php'  && php /var/www/html/server.php && npm run build) >/proc/1/fd/1 2>&1" >> mycron ; \
    crontab mycron ;  php /var/www/html/server.php ; apachectl -k restart ; sleep infinity

FROM node:20-alpine
# Install apache2, php, and required libs for both
WORKDIR /var/www
RUN apk add apache2 apache2-ctl busybox-extras curl php php-curl php-json
RUN rm -rf /var/www/html /var/www/localhost/htdocs && mkdir /var/www/localhost/htdocs
WORKDIR /var/www/localhost/htdocs
# Add dependency files first for better caching
ADD yarn.lock .
ADD gulpfile.js .
ADD package.json .
# Fetch npm deps
RUN npm i -g gulp && yarn install
# Add the application source code
ADD . .
# Build initial data
RUN php server.php
RUN chmod 777 /var/www/localhost/htdocs/build && node_modules/.bin/gulp
# Set index page
RUN sed  -i 's/DirectoryIndex index.html/DirectoryIndex index.php/' /etc/apache2/httpd.conf

EXPOSE 80
ENTRYPOINT crond -b && \
    echo "0 0,6,12,18 * * * (cd /var/www/localhost/htdocs && echo 'syncing server.php' && php /var/www/localhost/htdocs/server.php && npm run build) >/proc/1/fd/1 2>&1" >> mycron ; \
    crontab mycron ; php /var/www/localhost/htdocs/server.php && npm run build ; apachectl -k restart ; sleep infinity

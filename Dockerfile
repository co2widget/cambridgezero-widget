FROM debian:buster-slim
ADD . .
# Install Git, PHP, Node and NPM
WORKDIR /var/www
RUN apt-get update && apt-get install -y git apache2 php libapache2-mod-php php-curl curl nodejs npm
# Get the application source code
RUN rm -rf /var/www/html && git clone https://github.com/co2widget/cambridgezero-widget.git /var/www/html
WORKDIR /var/www/html
# for now limiting branch to this known one...
RUN git checkout 2af00077fa422c2aa46cff4c48bd15e5ff3bd0f5
# Get nvm etc
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | bash
RUN npm i && npm rebuild node-sass
ARG URL
ENV URL ${URL:-co2widget.com}
RUN node_modules/.bin/gulp && chmod 777 /var/www/html/build && php server.php
RUN apt-get install -y cron

ENTRYPOINT echo "* * * * * echo 'syncing server.php' && php /var/www/html/server.php && npm run build && apachectl -k restart" >> mycron ; \
    crontab mycron

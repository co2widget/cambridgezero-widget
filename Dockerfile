FROM debian:buster-slim
ADD . .
RUN apt-get update
# Install Git, PHP, Node and NPM
RUN apt-get install -y apache2 php libapache2-mod-php php-curl curl git
# Get the application source code
RUN rm -rf /var/www/html && mkdir /var/www/html && chmod 777 /var/www/html
RUN git clone https://github.com/co2widget/cambridgezero-widget.git /var/www/html
# for now limiting branch to this known one...
RUN git checkout 2af00077fa422c2aa46cff4c48bd15e5ff3bd0f5
# Get nvm etc
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | bash
ENV NVM_DIR "/root/.nvm"
RUN . /root/nvm.sh && nvm install 12.18.2 && cd /var/www/html && npm i && npm rebuild node-sass
ARG URL
RUN node_modules/.bin/gulp && chmod 777 /var/www/html/build && php server.php

ENTRYPOINT echo "0 0,6,12,18 * * * echo 'syncing server.php' && php /var/www/html/server.php && npm run build && apachectl -k restart" >> mycron ; \
    crontab mycron

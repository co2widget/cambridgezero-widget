#! /bin/bash
# Install PHP and dependencies from apt
apt-get update
apt-get install -y apache2 php libapache2-mod-php php-curl

# Install Git, Node and NPM
apt-get install -y curl git

# Get the application source code
rm -rf /var/www/html
mkdir /var/www/html
chmod 777 /var/www/html
git clone https://github.com/co2widget/cambridgezero-widget.git /var/www/html
git checkout 2af00077fa422c2aa46cff4c48bd15e5ff3bd0f5

# Run NPM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm install 12.18.2
nvm use 12.18.2
cd /var/www/html
npm i
npm rebuild node-sass
URL=co2widget.com node_modules/.bin/gulp
chmod 777 /var/www/html/build
php server.php
sudo apachectl -k restart

#Create the cron job
#write out current crontab
crontab -l > mycron
#echo new cron into cron file
echo "0 0,6,12,18 * * * echo 'syncing server.php' && php /var/www/html/server.php && npm run build && apachectl -k restart" >> mycron
#install new cron file
crontab mycron
rm mycron

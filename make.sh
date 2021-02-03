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
git clone -b gc-deployment https://rscoates:a0bd981f2385a34ba587d298e8c210f655540cdc@github.com/ChrisButterworth/cambridgezero-widget.git /var/www/html

# Run NPM
apt-get install -y nodejs npm
cd /var/www/html
npm i
URL=co2widget.com node_modules/.bin/gulp
chmod 777 /var/www/html/build
php server.php
sudo apachectl -k restart

#Create the cron job
#write out current crontab
crontab -l > mycron
#echo new cron into cron file
echo "0 0 * * * * echo 'syncing server.php' && php /var/www/html server.php" >> mycron
#install new cron file
crontab mycron
rm mycron
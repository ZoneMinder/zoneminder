#!/bin/bash

# Start MySQL
/usr/bin/mysqld_safe & 
sleep 5

# Create the ZoneMinder database
mysql -u root < db/zm_create.sql

# Add the ZoneMinder DB user
mysql -u root -e "grant insert,select,update,delete,lock tables,alter on zm.* to 'zm'@'localhost' identified by 'zm'"

# Install the ZoneMinder apache vhost file
wget --quiet https://raw.github.com/kylejohnson/puppet-zoneminder/master/files/zoneminder -O /etc/apache2/sites-enabled/000-default

# Restart apache
service apache2 restart

# Start ZoneMinder
/usr/local/bin/zmpkg.pl start

#!/bin/bash

if [ -f "/setup.done" ]; then
  exit 0
fi

# Set MySQL in the volume
rm -rf /var/lib/mysql/*
chown -R mysql:mysql /var/lib/mysql
mysqld --initialize-insecure


# Start MySQL
# For Xenial the following won't start mysqld
#/usr/bin/mysqld_safe & 
# Use this instead:
service mysql start

# Give MySQL time to wake up
SECONDS_LEFT=120
while true; do
  sleep 1
  mysqladmin ping
  if [ $? -eq 0 ];then
    break; # Success
  fi
  let SECONDS_LEFT=SECONDS_LEFT-1 

  # If we have waited >120 seconds, give up
  # ZM should never have a database that large!
  # if $COUNTER -lt 120
  if [ $SECONDS_LEFT -eq 0 ];then
    return -1;
  fi
done

# Create the ZoneMinder database
mysql -u root < db/zm_create.sql

# Add the ZoneMinder DB user
mysql -u root -e "grant insert,select,update,delete,lock tables,alter on zm.* to 'zmuser'@'localhost' identified by 'zmpass';"

# Activate CGI
a2enmod cgi

# Activate modrewrite
a2enmod rewrite

# Setting timezone
sed -i "s#;date.timezone =#date.timezone = $PHP_TIMEZONE#" /etc/php/7.0/apache2/php.ini

# Shut down mysql cleanly:
kill $(cat /var/run/mysqld/mysqld.pid)
sleep 5

touch /setup.done

exit 0

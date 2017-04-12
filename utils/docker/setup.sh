#!/bin/bash

# Start MySQL
/usr/bin/mysqld_safe & 

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

# Make ZM_LOGDIR
mkdir /var/log/zm

# Activate CGI
a2enmod cgi

# Activate modrewrite
a2enmod rewrite

# Shut down mysql cleanly:
kill $(cat /var/run/mysqld/mysqld.pid)
sleep 5

exit 0

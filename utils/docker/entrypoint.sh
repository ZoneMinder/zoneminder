#!/bin/bash
# ZoneMinder Dockerfile entrypoint script
# Written by Andrew Bauer <zonexpertconsulting@outlook.com>

###############
# SUBROUTINES #
###############

start_mysql () {
    service mysql start
    # Give MySQL time to wake up
    SECONDS_LEFT=120
    while true; do
      sleep 1
      mysqladmin ping > /dev/null 2>&1
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
}

close_mysql () {
    service mysql stop
    sleep 5
}

################
# MAIN PROGRAM #
################

echo

# Configure then start Mysql
if [ -n "$MYSQL_SERVER" ] && [ -n "$MYSQL_USER" ] && [ -n "$MYSQL_PASSWORD" ] && [ -n "$MYSQL_DB" ]; then
    sed -i -e "s/ZM_DB_NAME=zm/ZM_DB_NAME=$MYSQL_USER/g" /etc/zm.conf
    sed -i -e "s/ZM_DB_USER=zmuser/ZM_DB_USER=$MYSQL_USER/g" /etc/zm.conf
    sed -i -e "s/ZM_DB_PASS=zm/ZM_DB_PASS=$MYSQL_PASS/g" /etc/zm.conf
    sed -i -e "s/ZM_DB_HOST=localhost/ZM_DB_HOST=$MYSQL_SERVER/g" /etc/zm.conf
    start_mysql
else
    usermod -d /var/lib/mysql/ mysql
    start_mysql
    mysql -u root < /usr/local/share/zoneminder/db/zm_create.sql
    mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'zmuser'@'localhost' IDENTIFIED BY 'zmpass';"
fi
# Ensure we shut down mysql cleanly later:
trap close_mysql SIGTERM

# Configure then start Apache
if [ -n "$TZ" ]; then
    echo "date.timezone = $TZ" >> /etc/php/7.0/apache2/php.ini
else
    echo "date.timezone = UTC" >> /etc/php/7.0/apache2/php.ini
fi
service apache2 start

# Start ZoneMinder
echo " * Starting ZoneMinder video surveillance recorder"
/usr/local/bin/zmpkg.pl start
echo "   ...done."

# Stay in a loop to keep the container running
while :
do
    # perhaps output some stuff here or check apache & mysql are still running
    sleep 3600
done


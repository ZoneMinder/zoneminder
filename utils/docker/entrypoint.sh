#!/bin/bash
# ZoneMinder Dockerfile entrypoint script
# Written by Andrew Bauer <zonexpertconsulting@outlook.com>

###############
# SUBROUTINES #
###############

# Find ciritical files and perform sanity checks
initialize () {

    # Check to see if this script has access to all the commands it needs
    for CMD in mysqladmin sed usermod service; do
      type $CMD &> /dev/null

      if [ $? -ne 0 ]; then
        echo
        echo "ERROR: The script cannot find the required command \"${CMD}\"."
        echo
        exit 1
      fi
    done

    # Look in common places for the zoneminder config file - zm.conf
    for FILE in "/etc/zm.conf" "/etc/zm/zm.conf" "/usr/local/etc/zm.conf" "/usr/local/etc/zm/zm.conf"; do
        if [ -f $FILE ]; then
            ZMCONF=$FILE
            break
        fi
    done

    # Look in common places for the zoneminder startup perl script - zmpkg.pl
    for FILE in "/usr/bin/zmpkg.pl" "/usr/local/bin/zmpkg.pl"; do
        if [ -f $FILE ]; then
            ZMPKG=$FILE
            break
        fi
    done

    # Look in common places for the zoneminder dB creation script - zm_create.sql
    for FILE in "/usr/share/zoneminder/db/zm_create.sql" "/usr/local/share/zoneminder/db/zm_create.sql"; do
        if [ -f $FILE ]; then
            ZMCREATE=$FILE
            break
        fi
    done

    # Look in common places for the php.ini relevant to zoneminder - php.ini
    # Search order matters here because debian distros commonly have multiple php.ini's
    for FILE in "/etc/php/7.0/apache2/php.ini" "/etc/php5/apache2/php.ini" "/etc/php.ini" "/usr/local/etc/php.ini"; do
        if [ -f $FILE ]; then
            PHPINI=$FILE
            break
        fi
    done

    for FILE in $ZMCONF $ZMPKG $ZMCREATE $PHPINI; do 
        if [ -z $FILE ]; then
            echo
            echo "FATAL: This script was unable to determine one or more cirtical files. Cannot continue."
            echo
            echo "VARIABLE DUMP"
            echo "-------------"
            echo
            echo "Path to zm.conf: ${ZMCONF}"
            echo "Path to zmpkg.pl: ${ZMPKG}"
            echo "Path to zm_create.sql: ${ZMCREATE}"
            echo "Path to php.ini: ${PHPINI}"
            echo
            exit 98
        fi
    done
}

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
initialize

# Configure then start Mysql
if [ -n "$MYSQL_SERVER" ] && [ -n "$MYSQL_USER" ] && [ -n "$MYSQL_PASSWORD" ] && [ -n "$MYSQL_DB" ]; then
    sed -i -e "s/ZM_DB_NAME=zm/ZM_DB_NAME=$MYSQL_USER/g" $ZMCONF
    sed -i -e "s/ZM_DB_USER=zmuser/ZM_DB_USER=$MYSQL_USER/g" $ZMCONF
    sed -i -e "s/ZM_DB_PASS=zm/ZM_DB_PASS=$MYSQL_PASS/g" $ZMCONF
    sed -i -e "s/ZM_DB_HOST=localhost/ZM_DB_HOST=$MYSQL_SERVER/g" $ZMCONF
    start_mysql
else
    usermod -d /var/lib/mysql/ mysql
    start_mysql
    mysql -u root < $ZMCREATE
    mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'zmuser'@'localhost' IDENTIFIED BY 'zmpass';"
fi
# Ensure we shut down mysql cleanly later:
trap close_mysql SIGTERM

# Configure then start Apache
if [ -z "$TZ" ]; then
    $TZ = UTC
fi
echo "date.timezone = $TZ" >> $PHPINI
service apache2 start

# Start ZoneMinder
echo " * Starting ZoneMinder video surveillance recorder"
$ZMPKG start
echo "   ...done."

# Stay in a loop to keep the container running
while :
do
    # perhaps output some stuff here or check apache & mysql are still running
    sleep 3600
done


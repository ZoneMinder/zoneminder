#!/bin/bash

# Prepare proper amount of shared memory
# For H.264 cameras it may be necessary to increase the amount of shared memory
# to 2048 megabytes.
umount /dev/shm
mount -t tmpfs -o rw,nosuid,nodev,noexec,relatime,size=512M tmpfs /dev/shm

# Start MySQL
test -e /var/run/mysqld || install -m 755 -o mysql -g root -d /var/run/mysqld
su - mysql -s /bin/sh -c "/usr/bin/mysqld_safe > /dev/null 2>&1 &"

# Ensure we shut down mysql cleanly later:
trap close_mysql SIGTERM

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

# Restart apache
service apache2 restart

# Start ZoneMinder
/usr/local/bin/zmpkg.pl start && echo "Zone Minder started"

while :
do
    sleep 3600
done

function close_mysql {
    kill $(cat /var/run/mysqld/mysqld.pid)
    sleep 5
}

#!/bin/bash
# No longer needed. This was incorporated into startpackpack.sh

# Required, so that Travis marks the build as failed if any of the steps below fail
set -ev

# Install and test the zoneminder package (only) for Ubuntu Trusty
if [ ${OS} == "ubuntu" ] && [ ${DIST} == "trusty" ]; then 
    sudo gdebi --non-interactive build/zoneminder_*amd64.deb
    sudo chmod 644 /etc/zm/zm.conf 
    mysql -uzmuser -pzmpass zm < db/test.monitor.sql
    sudo /usr/bin/zmpkg.pl start
    sudo /usr/bin/zmfilter.pl -f purgewhenfull
fi


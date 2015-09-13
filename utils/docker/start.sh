#!/bin/bash

# Restart apache
service apache2 restart

# Start ZoneMinder
/usr/local/bin/zmpkg.pl start

# Start SSHD
/usr/sbin/sshd -D

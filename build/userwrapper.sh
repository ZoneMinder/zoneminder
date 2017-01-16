#!/bin/sh
useradd -u 1000 abauer
usermod -a -G sudo abauer 2>/dev/null || :
usermod -a -G wheel abauer 2>/dev/null || :
usermod -a -G adm abauer 2>/dev/null || :
export HOME=/home/abauer
exec chroot --userspec=abauer / $@

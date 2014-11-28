#!/bin/bash

set -e
set -o pipefail

with_timestamps() {
	while read -r line; do
	echo -e "$(date +%T)\t$line";
	done
}


install_deps() {

	sudo apt-get update -qq
	sudo apt-get install -y -qq zlib1g-dev apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf cmake libjpeg-turbo8-dev apache2-mpm-prefork libapache2-mod-php5 php5-cli libtheora-dev libvorbis-dev libvpx-dev libx264-dev 2>&1 > /dev/null 

}

install_deps | with_timestamps

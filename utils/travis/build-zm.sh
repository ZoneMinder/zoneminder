#!/bin/bash

set -e
set -o pipefail

with_timestamps() {
	while read -r line; do
	echo -e "$(date +%T)\t$line";
	done
}

cd $TRAVIS_BUILD_DIR

build_zm() {

	if [ "$ZM_BUILDMETHOD" = "autotools" ]; then
		./configure --prefix=/usr --with-libarch=lib/$DEB_HOST_GNU_TYPE --host=$DEB_HOST_GNU_TYPE --build=$DEB_BUILD_GNU_TYPE --with-mysql=/usr --with-ffmpeg=/usr --with-webdir=/usr/share/zoneminder/www --with-cgidir=/usr/libexec/zoneminder/cgi-bin --with-webuser=www-data --with-webgroup=www-data --enable-crashtrace=yes --disable-debug --enable-mmap=yes ZM_SSL_LIB=openssl
	fi

	if [ "$ZM_BUILDMETHOD" = "cmake" ]; then
		cmake -DCMAKE_INSTALL_PREFIX="/usr"
	fi

	make
	sudo make install

	if [ "$ZM_BUILDMETHOD" = "cmake" ]; then
		sudo ./zmlinkcontent.sh
	fi

}

build_zm | with_timestamps

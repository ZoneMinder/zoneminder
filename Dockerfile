# ZoneMinder, you need the GIT repository code and submodules (git submodule update --init --recursive)

FROM ubuntu:xenial
MAINTAINER Markos Vakondios <mvakondios@gmail.com>

# Resynchronize the package index files 
RUN apt-get update \
	&& DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    apache2 \
    build-essential \
    cmake \
    dh-autoreconf \
    dpatch \
    libapache2-mod-php \
    libarchive-zip-perl \
    libavcodec-dev \
    libavdevice-dev \
    libavfilter-dev \
    libavformat-dev \
    libavresample-dev \
    libav-tools \
    libavutil-dev \
    libbz2-dev \
    libcurl4-openssl-dev \
    libdate-manip-perl \
    libdbd-mysql-perl \
    libdbi-perl \
    libdevice-serialport-perl \
    libjpeg-turbo8 \
    libjpeg-turbo8-dev \
    libmime-lite-perl \
    libmime-perl \
    libmp4v2-dev \
    libmysqlclient-dev \
    libnetpbm10-dev \
    libpcre3 \
    libpcre3-dev \
    libpolkit-gobject-1-dev \
    libpostproc-dev \
    libssl-dev \
    libswscale-dev \
    libsys-mmap-perl \
    libtheora-dev \
    libtool \
    libv4l-dev \
    libvlc5 \
    libvlccore8 \
    libvlccore-dev \
    libvlc-dev \
    libvorbis-dev \
    libvpx-dev \
    libwww-perl \
    libx264-dev \
    mysql-client \
    mysql-server \
    php \
    php-cli \
    php-mysql \
    vlc-data \
    yasm \
    zip \
	&& rm -rf /var/lib/apt/lists/*

# Copy local code into our container
ADD cmake /ZoneMinder/cmake/
ADD db /ZoneMinder/db/
ADD misc /ZoneMinder/misc/
ADD onvif /ZoneMinder/onvif/
ADD scripts /ZoneMinder/scripts/
ADD src /ZoneMinder/src/
ADD umutils /ZoneMinder/umutils/
ADD web /ZoneMinder/web/
ADD cmakecacheimport.sh CMakeLists.txt version zm.conf.in zmconfgen.pl.in zmlinkcontent.sh.in zoneminder-config.cmake /ZoneMinder/
ADD conf.d /ZoneMinder/conf.d

# Change into the ZoneMinder directory
WORKDIR /ZoneMinder

# Setup the ZoneMinder build environment
#RUN aclocal && autoheader && automake --force-missing --add-missing && autoconf

# Configure ZoneMinder
#RUN ./configure --with-libarch=lib/$DEB_HOST_GNU_TYPE --disable-debug --host=$DEB_HOST_GNU_TYPE --build=$DEB_BUILD_GNU_TYPE --with-mysql=/usr  --with-webdir=/var/www/zm --with-ffmpeg=/usr --with-cgidir=/usr/lib/cgi-bin --with-webuser=www-data --with-webgroup=www-data --enable-mmap=yes --enable-onvif ZM_SSL_LIB=openssl ZM_DB_USER=zm ZM_DB_PASS=zm
RUN cmake .

# Build & install ZoneMinder
RUN make && make install

# ensure writable folders
RUN ./zmlinkcontent.sh

# Adding the start script
ADD utils/docker/start.sh /tmp/start.sh

# Settings rights for /usr/local/share/zoneminder/
RUN chown -R www-data:www-data /usr/local/share/zoneminder/

# Adding apache virtual hosts file
RUN cp misc/apache.conf /etc/apache2/sites-available/000-default.conf

# Expose http port
EXPOSE 80

VOLUME /var/lib/zoneminder/images /var/lib/zoneminder/events /var/lib/mysql /var/log/zm

# To speed up configuration testing, we put it here
ADD utils/docker /ZoneMinder/utils/docker/

CMD /ZoneMinder/utils/docker/setup.sh && /ZoneMinder/utils/docker/start.sh >/var/log/start.log 2>&1 & /bin/bash

# Run example docker run -it -p 1080:80 -e PHP_TIMEZONE='Europe/Paris' -v /disk/zoneminder/events:/var/lib/zoneminder/events -v /disk/zoneminder/images:/var/lib/zoneminder/images -v /disk/zoneminder/mysql:/var/lib/mysql -v /disk/zoneminder/logs:/var/log/zm --name zoneminder zoneminder/zoneminder

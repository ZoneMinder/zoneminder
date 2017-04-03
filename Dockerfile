# ZoneMinder

FROM ubuntu:trusty
MAINTAINER Kyle Johnson <kjohnson@gnulnx.net>

# Resynchronize the package index files 
RUN apt-get update && \
	DEBIAN_FRONTEND=noninteractive apt-get install -y \
	libpolkit-gobject-1-dev build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev \
	libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 \
	libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm cmake libjpeg-turbo8-dev \
	libjpeg-turbo8 libtheora-dev libvorbis-dev libvpx-dev libx264-dev libmp4v2-dev libav-tools mysql-client \
	apache2 php5 php5-mysql apache2-mpm-prefork libapache2-mod-php5 php5-cli \
	mysql-server libvlc-dev libvlc5 libvlccore-dev libvlccore7 vlc-data libcurl4-openssl-dev \
	libavformat-dev libswscale-dev libavutil-dev libavcodec-dev libavfilter-dev \
	libavresample-dev libavdevice-dev libpostproc-dev libv4l-dev libtool libnetpbm10-dev \
	libmime-lite-perl dh-autoreconf dpatch \
	&& apt-get clean

# Copy local code into our container
ADD . /ZoneMinder

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

# give files in /usr/local/share/zoneminder/
RUN chown -R www-data:www-data /usr/local/share/zoneminder/

# Adding apache virtual hosts file
RUN cp misc/apache.conf /etc/apache2/sites-available/000-default.conf
ADD utils/docker/phpdate.ini /etc/php5/apache2/conf.d/25-phpdate.ini

# Expose http ports
EXPOSE 80

# Initial database and apache setup:
RUN "/ZoneMinder/utils/docker/setup.sh"

CMD ["/ZoneMinder/utils/docker/start.sh"]


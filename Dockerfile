# ZoneMinder

FROM ubuntu:precise
MAINTAINER Kyle Johnson <kjohnson@gnulnx.net>

# Let the conatiner know that there is no tty
ENV DEBIAN_FRONTEND noninteractive

# Resynchronize the package index files 
RUN echo "deb http://archive.ubuntu.com/ubuntu precise main universe" > /etc/apt/sources.list
RUN apt-get update
RUN apt-get upgrade -y

# Install the prerequisites
RUN apt-get install -y build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm subversion automake autoconf libjpeg-turbo8-dev libjpeg-turbo8 libtheora-dev libvorbis-dev libvpx-dev libx264-dev libmp4v2-dev ffmpeg git wget mysql-client apache2 php5 php5-mysql apache2-mpm-prefork libapache2-mod-php5 php5-cli openssh-server mysql-server

# Grab the latest ZoneMinder code
RUN git clone https://github.com/ZoneMinder/ZoneMinder.git

# Change into the ZoneMinder directory
WORKDIR ZoneMinder

# Setup the ZoneMinder build environment
RUN aclocal && autoheader && automake --force-missing --add-missing && autoconf

# Configure ZoneMinder
RUN ./configure --with-libarch=lib/$DEB_HOST_GNU_TYPE --disable-debug --host=$DEB_HOST_GNU_TYPE --build=$DEB_BUILD_GNU_TYPE --with-mysql=/usr  --with-webdir=/var/www/zm --with-ffmpeg=/usr --with-cgidir=/usr/lib/cgi-bin --with-webuser=www-data --with-webgroup=www-data --enable-mmap=yes ZM_SSL_LIB=openssl ZM_DB_USER=zm ZM_DB_PASS=zm

# Build ZoneMinder
RUN make

# Install ZoneMinder
RUN make install

# Adding the start script
ADD start.sh /tmp/start.sh

# Make start script executable
RUN chmod 755 /tmp/start.sh

# Expose ssh and http ports
EXPOSE 80
EXPOSE 22


CMD "/tmp/start.sh"

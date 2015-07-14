# ZoneMinder

FROM ubuntu:precise
MAINTAINER Kyle Johnson <kjohnson@gnulnx.net>

# Let the container know that there is no tty
ENV DEBIAN_FRONTEND noninteractive

# Resynchronize the package index files 
RUN apt-get update && apt-get install -y \
	libpolkit-gobject-1-dev build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev \
	libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 \
	libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf libjpeg-turbo8-dev \
	libjpeg-turbo8 libtheora-dev libvorbis-dev libvpx-dev libx264-dev libmp4v2-dev ffmpeg mysql-client \
	apache2 php5 php5-mysql apache2-mpm-prefork libapache2-mod-php5 php5-cli openssh-server \
	mysql-server libvlc-dev libvlc5 libvlccore-dev libvlccore5 vlc-data vlc libcurl4-openssl-dev

# Copy local code into our container
ADD . /ZoneMinder

# Change into the ZoneMinder directory
WORKDIR /ZoneMinder

# Setup the ZoneMinder build environment
RUN aclocal && autoheader && automake --force-missing --add-missing && autoconf

# Configure ZoneMinder
RUN ./configure --with-libarch=lib/$DEB_HOST_GNU_TYPE --disable-debug --host=$DEB_HOST_GNU_TYPE --build=$DEB_BUILD_GNU_TYPE --with-mysql=/usr  --with-webdir=/var/www/zm --with-ffmpeg=/usr --with-cgidir=/usr/lib/cgi-bin --with-webuser=www-data --with-webgroup=www-data --enable-mmap=yes ZM_SSL_LIB=openssl ZM_DB_USER=zm ZM_DB_PASS=zm

# Build ZoneMinder
RUN make

# Install ZoneMinder
RUN make install

# Adding the start script
ADD utils/docker/start.sh /tmp/start.sh

# Ensure we can run this
# TODO - Files ADD'ed have 755 already...why do we need this?
RUN chmod 755 /tmp/start.sh

# Creating SSH privledge escalation dir
RUN mkdir /var/run/sshd

# Adding apache virtual hosts file
ADD utils/docker/apache-vhost /etc/apache2/sites-enabled/000-default

# Set the root passwd
RUN echo 'root:root' | chpasswd

# Expose ssh and http ports
EXPOSE 80
EXPOSE 22

CMD "/tmp/start.sh"

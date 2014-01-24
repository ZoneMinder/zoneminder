# ZoneMinder
#
# Version 0.0.1
# GOAL Ability to quickly stand up a ZoneMinder server in order to test code

FROM ubuntu
MAINTAINER Kyle Johnson <kjohnson@gnulnx.net>

# Let the conatiner know that there is no tty
ENV DEBIAN_FRONTEND noninteractive

# Resynchronize the package index files 
RUN apt-get update

# Install the MySQL server
RUN apt-get install -y mysql-server

# Install apache and php
RUN apt-get install -y apache2 php5 php5-mysql apache2-mpm-prefork libapache2-mod-php5 php5-cli

# Install the prerequisites required to build ZoneMinder and ffmpeg
RUN apt-get install -y build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm subversion automake autoconf libjpeg-turbo8-dev libjpeg-turbo8 libtheora-dev libvorbis-dev libvpx-dev libx264-dev libmp4v2-dev ffmpeg git wget

# Grab the latest ZoneMinder code
RUN git clone https://github.com/ZoneMinder/ZoneMinder.git

# Change into the ZoneMinder directory
WORKDIR ZoneMinder

# Grab the configure script from my puppet-zoneminder installer
RUN wget https://raw.github.com/kylejohnson/puppet-zoneminder/master/templates/configure.sh.erb -O configure.sh

# Run configure
RUN bash configure.sh

# Build ZoneMinder
RUN make

# Install ZoneMinder
RUN make install

# Create the ZoneMinder database
RUN mysql -u root < db/zm_create.sql

# Create the ZoneMinder database user
RUN mysql -u root -e "grant insert,select,update,delete on zm.* to 'zm'@'localhost' identified by 'zmpass'"

# Install the ZoneMinder apache vhost file
RUN wget https://raw.github.com/kylejohnson/puppet-zoneminder/master/files/zoneminder -O /etc/apache2/sites-enabled/000-default

# Restart apache
RUN service apache2 restart

USER www-data
ENTRYPOINT ["zmpkg.pl", "start"]

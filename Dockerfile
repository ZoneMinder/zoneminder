# ZoneMinder

FROM ubuntu:14.04
MAINTAINER Kyle Johnson <kjohnson@gnulnx.net>

# Let the container know that there is no tty
ENV DEBIAN_FRONTEND noninteractive

# Resynchronize the package index files 
RUN apt-get update && apt-get install -y cmake zlib1g-dev \
	libcurl4-gnutls-dev libjpeg-dev libssl-dev \
	libmysqlclient-dev build-essential

# Copy local code into our container
ADD . /source

# Change into the ZoneMinder directory
WORKDIR /source/build

# Setup the ZoneMinder build environment
RUN cmake ..

# Build ZoneMinder
RUN make

# Install ZoneMinder
RUN make install

# Adding the start script

# Expose ssh and http ports
EXPOSE 80
EXPOSE 22

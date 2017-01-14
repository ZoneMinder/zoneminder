#!/bin/sh
# packpack setup file for ZoneMinder project
# Written by Andrew Bauer

# This script is jsut a first start. It will change siginificantly as support
# for more distros is added.

ln -s distros/redhat rpm
mkdir -p build
curl https://zmrepo.zoneminder.com/f/25/i386/zmrepo-25-1.fc25.noarch.rpm > build/zmrepo-25-1.fc25.noarch.rpm
curl -L https://github.com/FriendsOfCake/crud/archive/v3.0.10.tar.gz > build/crud-3.0.10.tar.gz
git clone https://github.com/packpack/packpack.git packpack
patch -p1 < utils/packpack/autosetup.patch
packpack/packpack -f utils/packpack/fedora25_package.mk fedora25_package


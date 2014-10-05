#!/bin/bash

set -ev


git clone -b n2.4.1 --depth=1 git://source.ffmpeg.org/ffmpeg.git 
cd ffmpeg 
./configure --enable-shared --enable-swscale --enable-gpl  --enable-libx264 --enable-libvpx --enable-libvorbis --enable-libtheora 
make -j `grep processor /proc/cpuinfo|wc -l` 
sudo make install 
sudo make install-libs

# Overview

Docker allows you to quickly spin up containers.  The ZoneMinder dockerfile will spin
up an Ubuntu 12.04 container with mysql, apache, php and then compile and install ZoneMinder (from master).

Afterwards you can connect to this container over SSH to check out the latest code.

This is still a bit of a work in progress.

## How To Use

1. Pull it
```sudo docker pull ubuntu:precise```
2. Built it
```sudo docker build -t yourname/zoneminder github.com/ZoneMinder/ZoneMinder```
3. Run it
```CID=$(sudo docker run -d -p 222:22 -p 8080:80 -name zoneminder yourname/zoneminder)```
4. Use it -- you can now SSH to port 222 on your host as user root with password root.
You can also browse to your host on port 8080 to access the zoneminder web interface

## Use Cases

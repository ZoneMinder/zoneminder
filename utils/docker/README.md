# Overview

[Docker](https://www.docker.io/) allows you to quickly spin up application containers, 
which are similar to very lightweight virtual machines. The ZoneMinder Dockerfile will
start an Ubuntu 12.04 container with MySql, Apache, and PHP properly configured, and 
will then compile and install ZoneMinder.

It will also start an SSH server that you can use to log into the container.

This is still a bit of a work in progress.

## How To Use

1. Install [Docker](https://www.docker.io/)  
2. Build ZoneMinder container
```sudo docker build -t yourname/zoneminder github.com/ZoneMinder/ZoneMinder```
3. Run it
```CID=$(sudo docker run -d -p 222:22 -p 8080:80 -name zoneminder yourname/zoneminder)```
4. Use it -- you can now SSH to port 222 on your host as user root with password root.
You can also browse to your host on port 8080 to access the zoneminder web interface

## Developing With Docker

If you wish to contribute to ZoneMinder, Docker can be helpful. By re-running 
```docker build``` in your working directory, any code modifications you have 
made will be pulled into a new container, compiled, and started, all without 
modifying your base system. 

Development is not totally without annoyances, as any change
to the project will require a full rebuild of all C++. Docker notices that the 
directory which has been ADD'ed is now different, and therefore all steps after
the ADD command must be recomputed. A fix for this is to update the Dockerfile to
move the configure and make commands into start.sh, and then use a volume mount 
to cache the build directory (I think it's ```/tmp```) on your host filesystem. 
This would be really useful for a developer, and would remove the annoying build 
problem, but some of the Docker push/pull benefits would be lost.  

Docker containers can be both CPU and memory limited, so this can be a practical 
method to compile or run multiple development builds of ZoneFinder simultaneously
without taxing your host system. 

## Use Cases

## TODO
- Describe how to connect to monitors by mounting devices
- Create a 'development' dockerfile to remove the need to rebuild the entire project
  after each small change

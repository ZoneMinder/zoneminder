How to install ZoneMinder on Raspberry PI 1 or 2 with Raspbian 8.1 (Jessy) 64 bit
==================================================================================

A special Thanks to "Felixr" and "BBungle" from witch I took the initial FAQ.

Prereq before starting:
-----------------------
 SD card created with raspbian Jessie 64bit
 filesystem is extended.
 the systems boots in console mode with autologin disabled (X is disabled)

Log in as pi
--------------
 - pi  (followed by your root password)
 - sudo to root
 - sudo su
Configure your network and Set static IP address
---------------------------------------------------
 nano /etc/network/interfaces
 Make changes similar to this:
 
 auto eth0
 #allow-hotplug eth0 (make sure this is commented or you will get 2 ip's)
 iface eth0 inet static
 address 192.168.1.10
 netmask 255.255.255.0
 gateway 192.168.1.1
 dns-nameservers 192.168.1.1

Update Raspbian Sources
-------------------------
 apt-get update

 Check to be sure everything is up to date for the raspbian distribution
  apt-get upgrade
  apt-get dist-upgrade
 Add the Debian Jessie backports
  nano /etc/apt/sources.list
  add to the top of the list:
   deb http://http.debian.net/debian jessie-backports main
   Ctrl+o Enter to save CTRL+x to exit
  add priority to this repository
   nano /etc/apt/preferences.d/zoneminder
  add to the file:
   Package: *
   Pin: origin http.debian.net
   Pin-Priority: 1100
   Ctrl+o Enter to save CTRL+x to exit
 Update Sources
  apt-get update
  you will get a GPG error message like this:
  W: GPG error: http://http.debian.net jessie-backports InRelease: The following signatures couldn't be verified because the public key is not available: NO_PUBKEY 8B48AD6246925553    NO_PUBKEY 7638D0442B90D010
  Fix this as following:
   gpg --keyserver pgpkeys.mit.edu --recv-key  8B48AD6246925553
   gpg -a --export 8B48AD6246925553 | sudo apt-key add -
   gpg --keyserver pgpkeys.mit.edu --recv-key  7638D0442B90D010
   gpg -a --export 7638D0442B90D010 | sudo apt-key add -
 Update Sources again (now should go fine)
  apt-get update 
 Check to be sure everything is up to date for the raspbian distribution
  apt-get upgrade
  apt-get dist-upgrade

Install PHP, and MySQL server (This installs MySQL server 5.5. If you want to use MySQL 5.6 follow the instructions Install MySQL 5.6 on Debian Jessie (using mariadb also works fine, as tested on Debian 8.2, replacement package for mysql-server is then mariadb-server)
 apt-get install  php5 mysql-server php-pear php5-mysql
Install Zoneminder
 apt-get install zoneminder
You may need to install "extra" VLC components (I will check this and edit as needed)
 apt-get install libvlc-dev libvlccore-dev vlc
Create Zoneminder database in MySQL (Note: this also creates the default Zoneminder user and permissions in MySQL)
This next step creates a file which contained the MySQL user and password. Otherwise you will have to enter the user and password on the command line which is not secure!
Go to the root directory
 cd ~
Create a hidden password file
 nano .my.cnf
Enter this content (but use your MySQL root password!)
 [client]
 user=root
 password=(mysqlpass)
 Ctrl+o Enter to save
 CTRL+x to exit
Create database (press ENTER after each command)
 mysql < /usr/share/zoneminder/db/zm_create.sql 
 mysql -e "grant select,insert,update,delete,create on zm.* to 'zmuser'@localhost identified by 'zmpass';"
Remove password file
 rm .my.cnf
Set permissions of /etc/zm/zm.conf to root:www-data 740
 chmod 740 /etc/zm/zm.conf
 chown root:www-data /etc/zm/zm.conf

Enable Zoneminder service to start at boot
 systemctl enable zoneminder.service
Add www-data to the sudo group (to enable use of local video devices)
 adduser www-data video
Start Zoneminder
 systemctl start zoneminder.service
Check to see that Zoneminder is running
 systemctl status zoneminder.service
Enable CGI and Zoneminder configuration in Apache.
 a2enmod cgi
 a2enconf zoneminder
Restart Apache
 service apache2 restart

You may be tempted to try Zoneminder at this point but there is one setting you will need to change from the web gui. Read on!!!
Optional: Install Cambozola (needed if you use Internet Explorer)
 cd /usr/src && wget http://www.andywilcock.com/code/cambozola/cambozola-latest.tar.gz
 tar -xzvf cambozola-latest.tar.gz
replace 936 with cambozola version downloaded
 cp cambozola-0.936/dist/cambozola.jar /usr/share/zoneminder
Kernel shared memory settings:
Set shared memory for 512MB RPi board: 1) 128MB shhmax shared:
 sudo su -
 echo "kernel.shmmax = 134217728" >> /etc/sysctl.conf
 exit
2) 2MB shmall pages:
 sudo su -
 echo "kernel.shmall = 2097152" >> /etc/sysctl.conf
 exit
You should now be able to access the web server using http://servername
Open Zoneminder in web browser
 http://serverip/zm
Click Options
Uncheck: Check with zoneminder.com for updated versions (?) click Save
Click Images tab
Check Is the (optional) cambozola java streaming client installed (?) Click Save
Click Paths
Change PATH_ZMS from /cgi-bin/nph-zms to /zm/cgi-bin/nph-zms Click Save
Optional: under Paths change PATH_SWAP to /dev/shm (puts this process in RAM drive) Click Save
Restart Zoneminder
Your Zoneminder install is now ready to add cameras!

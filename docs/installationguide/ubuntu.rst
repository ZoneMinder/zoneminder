Ubuntu
======

.. contents::

Easy Way: Install ZoneMinder from a package (Ubuntu 15.x+)
-----------------------------------------------------------
These instructions are for a brand new ubuntu 15.04 system which does not have ZM installed.

**Step 1**: Make sure we add the correct packages

::

	sudo  add-apt-repository ppa:iconnor/zoneminder
	sudo apt-get update

if you don't have mysql already installed:

::

	sudo apt-get install mysql-server 

This will ask you to set up a master password for the DB (you are asked for the mysql root password when installing mysql server).

**Step 2**: Install ZoneMinder

::

	sudo apt-get install zoneminder

**Step 3**: Configure the Database

::

	sudo mysql -uroot -p < /usr/share/zoneminder/db/zm_create.sql
	mysql -uroot -p -e "grant select,insert,update,delete,create,alter,index,lock tables on zm.* to 'zmuser'@localhost identified by 'zmpass';"

You don't really need this, but no harm (needed if you are upgrading)

::

	sudo /usr/bin/zmupdate.pl

**Step 4**: Configure systemd to recognize ZoneMinder and configure Apache correctly:

::

	sudo systemctl enable zoneminder
	sudo a2enconf zoneminder
	sudo a2enmod cgi
	sudo chown -R www-data:www-data /usr/share/zoneminder/


We need this for API routing to work:

::

	sudo a2enmod rewrite

This is probably a bug with iconnor's PPA as of Oct 3, 2015 with package 1.28.107. After installing, ``zm.conf`` does not have the right read permissions, so we need to fix that. This may go away in future PPA releases:

::

	sudo chown www-data:www-data /etc/zm/zm.conf 

We also need to install php5-gd (as of 1.28.107, this is not installed)

::

	sudo apt-get install php5-gd

**Step 5**: Edit Timezone in PHP

::

	vi /etc/php5/apache2/php.ini

Look for [Date] and inside it you will see a date.timezone
that is commented. remove the comment and specific your timezone.
Please make sure the timezone is valid (see this: http://php.net/manual/en/timezones.php)

In my case:

::

	date.timezone = America/New_York

**Step 6**: Restart services

::

	sudo service apache2 reload
	sudo systemctl restart zoneminder


**Step 7: make sure live streaming works**: Make sure you can view Monitor streams:

startup ZM console in your browser, go to ``Options->Path`` and make sure ``PATH_ZMS`` is set to ``/zm/cgi-bin/nph-zms`` and restart ZM (you should not need to do this for packages, as this should automatically work)


**Step 8**: If you have changed your DB login/password from zmuser/zmpass, the API won't know about it

If you changed the  DB password **after** installing ZM, the APIs will not be able to connect to the DB.

If you have, go to ``zoneminder/www/api/app/Config`` & Edit ``database.php``

There is a class there called ``DATABASE_CONFIG`` - change the ``$default`` array to reflect your new details. Example:

::

	public $default = array(
			'datasource' => 'Database/Mysql',
			'persistent' => false,
			'host' => 'localhost',
			'login' => 'mynewDBusername',
			'password' => 'mynewDBpassword'
			'database' => 'zm',
			'prefix' => '',
			//'encoding' => 'utf8',
		);


You are done. Lets proceed to make sure everything works:

Making sure ZM and APIs work:

1. open up a browser and go to ``http://localhost/zm`` - should bring up ZM
2. (OPTIONAL - just for peace of mind) open up a tab and go to ``http://localhost/zm/api`` - should bring up a screen showing CakePHP version with some green color boxes. Green is good. If you see red, or you don't see green, there may be a problem (should not happen). Ignore any warnings in yellow saying "DebugKit" not installed. You don't need it
3. open up a tab in the same browser and go to ``http://localhost/zm/api/host/getVersion.json``

If it responds with something like:

::

	{
	    "version": "1.28.107",
	    "apiversion": "1.28.107.1"
	}


**Then your APIs are working**

Make sure ZM and APIs work with security:
1. Enable OPT_AUTH in ZM
2. Log out of ZM in browser
3. Open a NEW tab in the SAME BROWSER (important) and go to ``http://localhost/zm/api/host/getVersion.json`` - should give you "Unauthorized" along with a lot more of text
4. Go to another tab in the SAME BROWSER (important) and log into ZM
5. Repeat step 3 and it should give you the ZM and API version

**Congrats** your installation is complete


Easy Way: Install ZoneMinder from a package (Ubuntu 14.x)
-----------------------------------------------------------
**These instructions are for a brand new ubuntu 14.x system which does not have ZM installed.**

**Step 1:** Install ZoneMinder

::

	sudo  add-apt-repository ppa:iconnor/zoneminder
	sudo apt-get update
	sudo apt-get install zoneminder

(just press OK for the prompts you get)

**Step 2:** Set up DB

::

	sudo mysql -uroot -p < /usr/share/zoneminder/db/zm_create.sql
	mysql -uroot -p -e "grant select,insert,update,delete,create,alter,index,lock tables on zm.* to 'zmuser'@localhost identified by 'zmpass';"

**Step 3:** Set up Apache 

::

	sudo a2enconf zoneminder
	sudo a2enmod rewrite
	sudo a2enmod cgi

**Step 4:**:Some tweaks that will be needed:

Edit /etc/init.d/zoneminder:

add a ``sleep 10`` right after line 25 that reads ``echo -n "Starting $prog:"``
(The reason we need this sleep is to make sure ZM starts after mysqld starts)

As of Oct 3 2015, zm.conf is not readable by ZM. This is likely a bug and will go away in the next package

::

	sudo chown www-data:www-data /etc/zm/zm.conf



**Step 5**: If you have changed your DB login/password

If you changed the  DB password **after** installing ZM, the APIs will not be able to connect to the DB.

If you have, go to zoneminder/www/api/app/Config & Edit ``database.php``

There is a class there called ``DATABASE_CONFIG`` - change the ``$default`` array to reflect your new details. Example:

::

	public $default = array(
			'datasource' => 'Database/Mysql',
			'persistent' => false,
			'host' => 'localhost',
			'login' => 'mynewDBusername',
			'password' => 'mynewDBpassword'
			'database' => 'zm',
			'prefix' => '',
			//'encoding' => 'utf8',`
		);

We also need to install php5-gd (as of 1.28.107, this is not installed)

::

	sudo apt-get install php5-gd


**Step 6**: Edit Timezone in PHP

vi /etc/php5/apache2/php.ini
Look for [Date] and inside it you will see a date.timezone
that is commented. remove the comment and specific your timezone.
Please make sure the timezone is valid (see [this](http://php.net/manual/en/timezones.php))

In my case:

::

	date.timezone = America/New_York


**Step 7: make sure live streaming works**: Make sure you can view Monitor streams:

startup ZM console in your browser, go to ``Options->Path`` and make sure ``PATH_ZMS`` is set to ``/zm/cgi-bin/nph-zms`` and restart ZM (you should not need to do this for packages, as this should automatically work)



restart:

::

	sudo service apache2 restart
	/etc/init.d/zoneminder restart

**Step 8**: Making sure ZM and APIs work: (optional - only if you need APIs)

1. open up a browser and go to ``http://localhost/zm`` - should bring up ZM
2. (OPTIONAL - just for peace of mind) open up a tab and go to ``http://localhost/zm/api`` - should bring up a screen showing CakePHP version with some green color boxes. Green is good. If you see red, or you don't see green, there may be a problem (should not happen). Ignore any warnings in yellow saying "DebugKit" not installed. You don't need it
3. open up a tab in the same browser and go to ``http://localhost/zm/api/host/getVersion.json``

If it responds with something like:

::

	{
	    "version": "1.28.107",
	    "apiversion": "1.28.107.1"
	}

Then your APIs are working

Make sure you can view Monitor View:
1. Open up ZM, configure your monitors and verify you can view Monitor feeds. 
2. If not, open up ZM console in your browser, go to ``Options->Path`` and make sure ``PATH_ZMS`` is set to ``/zm/cgi-bin/nph-zms`` and restart ZM (you should not need to do this for packages, as this should automatically work)

Make sure ZM and APIs work with security:
1. Enable OPT_AUTH in ZM
2. Log out of ZM in browser
3. Open a NEW tab in the SAME BROWSER (important) and go to ``http://localhost/zm/api/host/getVersion.json`` - should give you "Unauthorized" along with a lot more of text
4. Go to another tab in the SAME BROWSER (important) and log into ZM
5. Repeat step 3 and it should give you the ZM and API version

**Congrats**  Your installation is complete




Harder Way: Build Package From Source
-------------------------------------------
(These instructions assume installation from source on a ubuntu 15.x+ system)

**Step 1:** First make sure you have the needed tools

::

	sudo apt-get update
	sudo apt-get install cmake git

**Step 2:** Next up make sure you have all the dependencies

::

	sudo apt-get install apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf libjpeg8-dev libjpeg8 apache2 libapache2-mod-php5 php5-cli libphp-serialization-perl libgnutls-dev libjpeg8-dev libavcodec-dev libavformat-dev libswscale-dev libavutil-dev libv4l-dev libtool ffmpeg libnetpbm10-dev libavdevice-dev libmime-lite-perl dh-autoreconf dpatch policykit-1 libpolkit-gobject-1-dev  libextutils-pkgconfig-perl libcurl3 libvlc-dev libcurl4-openssl-dev  curl php5-gd

(you are asked for the mysql root password when installing mysql server - put in a password that you'd like). 

**Step 3:** Download ZoneMinder source code and compile+install:

::

	git clone https://github.com/ZoneMinder/ZoneMinder.git
	cd ZoneMinder/
	git submodule init
	git submodule update
	cmake .
	make
	sudo make install

**Step 4:** Now make sure your symlinks to events and images are set correctly:

::

	sudo ./zmlinkcontent.sh

**Step 5:** Now lets make sure ZM has DB permissions to write to the DB:

::

	mysql -uroot -p -e "grant select,insert,update,delete,create,alter,index,lock tables on zm.* to 'zmuser'@localhost identified by 'zmpass';"

**Step 6:** Now lets create the DB & its tables that ZM needs

::

	mysql -uroot -p <db/zm_create.sql 


**Step 7:** Now we need to make sure Ubuntu 15 is able to start/stop zoneminder via systemd:

::

	sudo cp distros/ubuntu1504_cmake/zoneminder.service /lib/systemd/system

edit **/lib/systemd/system/zoneminder.service** file
* rename **/usr/bin/zmpkg** to **/usr/local/bin/zmpkg** everywhere 

(The step above is needed because when you compile from source, it installs to /usr/local/instead of /usr/)

**Step 8:** Now lets make sure systemd recognizes this file

::

	sudo systemctl daemon-reload
	sudo systemctl enable zoneminder.service

**Step 9:** Now lets work on Zoneminder's apache configuration:

::

	sudo cp distros/ubuntu1504_cmake/conf/apache2/zoneminder.conf /etc/apache2/conf-available/
	sudo a2enconf zoneminder
	sudo a2enmod cgi
	sudo a2enmod rewrite 
	sudo service apache2 reload


**Step 10:** Edit /etc/apache2/conf-available/zoneminder.conf and change **all** occurrences of:

* **/usr/lib/zoneminder/cgi-bin** to **/usr/local/libexec/zoneminder/cgi-bin**
* **/usr/share/zoneminder** to **/usr/local/share/zoneminder**

After editing your /etc/apache2/conf-available/zoneminder.conf should look like:

::

	ScriptAlias /zm/cgi-bin "/usr/local/libexec/zoneminder/cgi-bin"
	<Directory "/usr/local/libexec/zoneminder/cgi-bin">
	    Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
	    AllowOverride All
	    Require all granted
	</Directory>

	Alias /zm /usr/local/share/zoneminder/www
	<Directory /usr/local/share/zoneminder/www>
	  php_flag register_globals off
	  Options Indexes FollowSymLinks
	  <IfModule mod_dir.c>
	    DirectoryIndex index.php
	  </IfModule>
	</Directory>

	<Directory /usr/local/share/zoneminder/www/api>
	    AllowOverride All
	</Directory>

**Step 11:** Now lets make sure ZM can read/write to the zoneminder directory:

::

	sudo chown -R www-data:www-data /usr/local/share/zoneminder/


**Step 12:** Make sure you can view Monitor View

1. Open up ZM, configure your monitors and verify you can view Monitor feeds
2. If not, open up ZM console in your browser, go to ``Options->Path`` and make sure ``PATH_ZMS`` is set to ``/zm/cgi-bin/nph-zms`` and restart ZM

**Step 13**: Edit Timezone in PHP

vi /etc/php5/apache2/php.ini
Look for [Date] and inside it you will see a date.timezone
that is commented. remove the comment and specific your timezone.
Please make sure the timezone is valid (see http://php.net/manual/en/timezones.php)

In my case:

::

	date.timezone = America/New_York

**Step 14:** Finally, lets make a config change to apache (needed for htaccess overrides to work for APIs)
Edit  /etc/apache2/apache2.conf and add this:

::

	<Directory /usr/local/share>
		AllowOverride All
		Require all granted
	</Directory>

Restart apache

::

	sudo service apache2 reload

You are done. Lets proceed to make sure everything works:

Making sure ZM and APIs work:

1. open up a browser and go to ``http://localhost/zm`` - should bring up ZM
2. (OPTIONAL - just for peace of mind) open up a tab and go to ``http://localhost/zm/api`` - should bring up a screen showing CakePHP version with some green color boxes. Green is good. If you see red, or you don't see green, there may be a problem (should not happen). Ignore any warnings in yellow saying "DebugKit" not installed. You don't need it
3. open up a tab in the same browser and go to ``http://localhost/zm/api/host/getVersion.json``

If it responds with something like:

::

	{
	    "version": "1.28.107",
	    "apiversion": "1.28.107.1"
	}

Then your APIs are working

Make sure ZM and APIs work with security:
1. Enable OPT_AUTH in ZM
2. Log out of ZM in browser
3. Open a NEW tab in the SAME BROWSER (important) and go to ``http://localhost/zm/api/host/getVersion.json`` - should give you "Unauthorized" along with a lot more of text
4. Go to another tab in the SAME BROWSER (important) and log into ZM
5. Repeat step 3 and it should give you the ZM and API version

**Congrats** your installation is complete
 
Suggested changes to MySQL (Optional but recommended)
------------------------------------------------------
For most of you Zoneminder will run just fine with the default MySQL settings. There are a couple of settings that may, in time, provide beneficial especially if you have a number of cameras and many events with a lot of files. One setting we recommend is the "innodb_file_per_table" This will be a default setting in MySQL 5.6 but should be added in MySQL 5.5 which comes with Ubuntu 14.04. A description can be found here: http://dev.mysql.com/doc/refman/5.5/en/innodb-multiple-tablespaces.html

To add "innodb_file_per_table" edit the my.cnf file:

``vi /etc/mysql/my.cnf``
Under [mysqld] add
``innodb_file_per_table``

Save and exit.

Restart MySQL
``service mysql restart``



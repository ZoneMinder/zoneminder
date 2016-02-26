Ubuntu Instruction
===================

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

**Step 1:** Grab the package installer script

::

	wget https://raw.githubusercontent.com/ZoneMinder/ZoneMinder/master/utils/do_debian_package.sh
	chmod a+x do_debian_package.sh


**Step 2:** Update the system

::

	sudo apt-get update


**Step 3** Create the package

To build the latest master snapshot:

::

	./do_debian_package.sh `lsb_release -a 2>/dev/null | grep Codename | awk '{print $2}'`  `date +%Y%m%d`01 local master


To build the latest stable release:

::

	./do_debian_package.sh `lsb_release -a 2>/dev/null | grep Codename | awk '{print $2}'`  `date +%Y%m%d`01 local stable 


Note that the ``lsb_release -a 2>/dev/null | grep Codename | awk '{print $2}'`` part simply extracts your distribution name - like "vivid", "trusty" etc. You can always replace it by your distro name if you know it. As far as the script goes, it checks if your distro is "trusty" in which case it pulls in pre-systemd release configurations and if its not "trusty" it assumes its based on systemd and pulls in systemd related config files. 

(At the end the script will ask if you want to retain the checked out version of zoneminder. If you are a developer and are making local changes, make sure you select "y" so that the next time you do the build process mentioned here, it keeps your changes. Selecting any other value than "y" or "Y" will delete the checked out code and only retain the package)

This should now create a bunch of .deb files

**Step 4:** Install the package

::

	sudo gdebi zoneminder_<version>_<arch>.deb
	(example sudo gdebi zoneminder_1.29.0-vivid-2016012001_amd64.deb)


**This will report DB errors - ignore - you need to configure the DB and some other stuff**

**Step 5:** Post install configuration

::

	sudo mysql -uroot -p < /usr/share/zoneminder/db/zm_create.sql
	mysql -uroot -p -e "grant select,insert,update,delete,create,alter,index,lock tables on zm.* to 'zmuser'@localhost identified by 'zmpass';"

	sudo a2enmod cgi rewrite
	sudo a2enconf zoneminder



**Step 6:** Fix PHP TimeZone

``sudo vi /etc/php5/apache2/php.ini`` 

Look for [Date] and inside it you will see a date.timezone that is commented. remove the comment and specific your timezone. Please make sure the timezone is valid (see http://php.net/manual/en/timezones.php)

Example:

``date.timezone = America/New_York``

**Step 7:** Fix some key permission issues and make sure API works

::

	sudo chown www-data /etc/zm/zm.conf
	sudo chown -R www-data /usr/share/zoneminder/www/api/


**Step 8:**  Restart all services

::

	sudo service apache2 restart
	sudo service zoneminder restart

Check if ZM is running properly

::

	sudo service zoneminder status


**Step 9:** Make sure streaming works - set PATH_ZMS

open up ZM console in your browser, go to Options->Path and make sure ``PATH_ZMS`` is set to ``/zm/cgi-bin/nph-zms`` and restart ZM


**Step 10:** Make sure everything works

* point your browser to http://yourzmip/zm - you should see ZM console running
*  point your browser to http://yourzmip/zm/api/host/getVersion.json - you should see an API version
* Configure your monitors and make sure its all a-ok



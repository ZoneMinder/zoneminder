
API
====

This document will provide an overview of ZoneMinder's API. 

Overview
^^^^^^^^

In an effort to further 'open up' ZoneMinder, an API was needed.  This will
allow quick integration with and development of ZoneMinder.

The API is built in CakePHP and lives under the ``/api`` directory.  It
provides a RESTful service and supports CRUD (create, retrieve, update, delete)
functions for Monitors, Events, Frames, Zones and Config.

API Wrappers
^^^^^^^^^^^^^
- pyzm is a python wrapper for the ZoneMinder APIs. It supports both the legacy and new token based API, as well as ZM logs/ZM shared memory support. See `its project site <https://github.com/pliablepixels/pyzm/>`__ for more details. Documentation is `here <https://pyzm.readthedocs.io/en/latest/>`__.

API evolution
^^^^^^^^^^^^^^^

The ZoneMinder API has evolved over time. Broadly speaking the iterations were as follows:

* Prior to version 1.29, there really was no API layer. Users had to use the same URLs that the web console used to 'mimic' operations, or use an XML skin
* Starting version 1.29, a v1.0 CakePHP based API was released which continues to evolve over time. From a security perspective, it still tied into ZM auth and required client cookies for many operations. Primarily, two authentication modes were offered: 

  * You use cookies to maintain session state (``ZM_SESS_ID``)
  * You use an authentication hash to validate yourself, which included encoding personal information and time stamps which at times caused timing validation issues, especially for mobile consumers

* Starting version 1.34, ZoneMinder has introduced a new "token" based system which is based JWT. We have given it a '2.0' version ID. These tokens don't encode any personal data and can be statelessly passed around per request. It introduces concepts like access tokens, refresh tokens and per user level API revocation to manage security better. The internal components of ZoneMinder all support this new scheme now and if you are using the APIs we strongly recommend you migrate to 1.34 and use this new token system (as a side note, 1.34 also moves from MYSQL PASSWORD to Bcrypt for passwords, which is also a good reason why you should migate).
* Note that as of 1.34, both versions of API access will work (tokens and the older auth hash mechanism), however we no longer use sessions by default.  You will have to add a ``stateful=1`` query parameter during login to tell ZM to set a COOKIE and store the required info in the session. This option is only available if ``OPT_USE_LEGACY_API_AUTH`` is set to ON.

.. note::
	For the rest of the document, we will specifically highlight v2.0 only features. If you don't see a special mention, assume it applies for both API versions.



Enabling API
^^^^^^^^^^^^^

ZoneMinder comes with APIs enabled. To check if APIs are enabled, visit ``Options->System``. If ``OPT_USE_API`` is enabled, your APIs are active. 
For v2.0 APIs, you have an additional option right below it:

 * ``OPT_USE_LEGACY_API_AUTH`` which is enabled by default. When enabled, the `login.json` API (discussed later) will return both the old style (``auth=``) and new style (``token=``) credentials. The reason this is enabled by default is because any existing apps that use the API would break if they were not updated to use v2.0. (Note that zmNinja 1.3.057 and beyond will support tokens)

Enabling secret key
^^^^^^^^^^^^^^^^^^^

* It is **important** that you create a "Secret Key". This needs to be a set of hard to guess characters, that only you know. ZoneMinder does not create a key for you. It is your responsibility to create it. If you haven't created one already, please do so by going to ``Options->Systems`` and populating ``AUTH_HASH_SECRET``. Don't forget to save.
* If you plan on using V2.0 token based security, **it is mandatory to populate this secret key**, as it is used to sign the token. If you don't, token authentication will fail. V1.0 did not mandate this requirement.


Getting an API key
^^^^^^^^^^^^^^^^^^^^^^^

To get an API key:

::

    curl -XPOST -d "user=yourusername&pass=yourpassword" https://yourserver/zm/api/host/login.json


If you want to use a stateful connection, so you don't have to pass auth credentials with each query, you can use the following:

::

    curl -XPOST -c cookies.txt -d "user=yourusername&pass=yourpassword&stateful=1" https://yourserver/zm/api/host/login.json

This returns a payload like this for API v1.0:

::

  {
      "credentials": "auth=05f3a50e8f7<deleted>063",
      "append_password": 0,
      "version": "1.33.9",
      "apiversion": "1.0"
  }

Or for API 2.0:

::

  {
      "access_token": "eyJ0eXAiOiJK<deleted>HE",
      "access_token_expires": 3600,
      "refresh_token": "eyJ0eXAiOi<deleted>mPs",
      "refresh_token_expires": 86400,
      "credentials": "auth=05f3a50e8f7<deleted>063",  # only if OPT_USE_LEGACY_API_AUTH is enabled
      "append_password": 0, # only if OPT_USE_LEGACY_API_AUTH is enabled
      "version": "1.33.9",
      "apiversion": "2.0"
  }

Using these keys with subsequent requests
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Once you have the keys (a.k.a credentials (v1.0, v2.0) or token (v2.0)) you should now supply that key to subsequent API calls like this:

::

  # v1.0 or 2.0 based API access (will only work if AUTH_HASH_LOGINS is enabled

  # RECOMMENDED: v2.0 token based 
    curl -XGET  https://yourserver/zm/api/monitors.json?token=<access_token>

  # or, for legacy mode:

  curl -XGET  https://yourserver/zm/api/monitors.json?auth=<hex digits from 'credentials'>

  # or, if you specified -c cookies.txt in the original login request

  curl -b cookies.txt -XGET   https://yourserver/zm/api/monitors.json


.. NOTE::

	If you are using an ``HTTP GET`` request, the token/auth needs to be passed as a query parameter in the URL. If you are using an ``HTTP POST`` (like when you use the API to modify a monitor, for example), you can choose to pass the token as a data payload instead. The API layer discards data payloads for ``HTTP GET``. Finally, If you don't pass keys, you could also use cookies (not recommended as a general approach).

Key lifetime (v1.0)
^^^^^^^^^^^^^^^^^^^^^

If you are using the old credentials mechanism present in v1.0, then the credentials will time out based on PHP session timeout (if you are using cookies), or the value of ``AUTH_HASH_TTL`` (if you are using ``auth=`` and have enabled ``AUTH_HASH_LOGINS``) which defaults to 2 hours.  Note that there is no way to look at the hash and decipher how much time is remaining. So it is your responsibility to record the time you got the hash and assume it was generated at the time you got it and re-login before that time expires.

Key lifetime (v2.0)
^^^^^^^^^^^^^^^^^^^^^^

In version 2.0, it is easy to know when a key will expire before you use it. You can find that out from the ``access_token_expires`` and ``refresh_token_expires`` values (in seconds) after you decode the JWT key (there are JWT decode libraries for every language you want). You should refresh the keys before the timeout occurs, or you will not be able to use the APIs. 

Understanding access/refresh tokens (v2.0)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you are using V2.0, then you need to know how to use these tokens effectively:

* Access tokens are short lived. ZoneMinder issues access tokens that live for 3600 seconds (1 hour).
* Access tokens should be used for all subsequent API accesses. 
* Refresh tokens should ONLY be used to generate new access tokens. For example, if an access token lives for 1 hour, before the hour completes, invoke the ``login.json`` API above with the refresh token to get a new access token. ZoneMinder issues refresh tokens that live for 24 hours.
* To generate a new refresh token before 24 hours are up, you will need to pass your user login and password to ``login.json``

**To Summarize:**

* Pass your ``username`` and ``password`` to ``login.json`` only once in 24 hours to renew your tokens
* Pass your "refresh token" to ``login.json`` once in two hours (or whatever you have set the value of ``AUTH_HASH_TTL`` to) to renew your ``access token``
* Use your ``access token`` for all API invocations.

In fact, V2.0 will reject your request (if it is not to ``login.json``) if it comes with a refresh token instead of an access token to discourage usage of this token when it should not be used.

This minimizes the amount of sensitive data that is sent over the wire and the lifetime durations are made so that if they get compromised, you can regenerate or invalidate them (more on this later)

Understanding key security
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* Version 1.0 uses an MD5 hash to generate the credentials. The hash is computed over your secret key (if available), username, password and some time parameters (along with remote IP if enabled). This is not a secure/recommended hashing mechanism. If your auth hash is compromised, an attacker will be able to use your hash till it expires. To avoid this, you could disable the user in ZoneMinder. Furthermore, enabling remote IP (``AUTH_HASH_REMOTE_IP``) requires that you issue future requests from the same IP that generated the tokens. While this may be considered an additional layer for security, this can cause issues with mobile devices.

* Version 2.0 uses a different approach. The hash is a simple base64 encoded form of "claims", but signed with your secret key. Consider for example, the following access key:

::

  eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJab25lTWluZGVyIiwiaWF0IjoxNTU3OTQwNzUyLCJleHAiOjE1NTc5NDQzNTIsInVzZXIiOiJhZG1pbiIsInR5cGUiOiJhY2Nlc3MifQ.-5VOcpw3cFHiSTN5zfGDSrrPyVya1M8_2Anh5u6eNlI

If you were to use any `JWT token verifier <https://jwt.io>`__ it can easily decode that token and will show:

::

  {
  "iss": "ZoneMinder",
  "iat": 1557940752,
  "exp": 1557944352,
  "user": "admin",
  "type": "access"
  }
  Invalid Signature


Don't be surprised. JWT tokens, by default, are `not meant to be encrypted <https://softwareengineering.stackexchange.com/questions/280257/json-web-token-why-is-the-payload-public>`__. It is just an assertion of a claim. It states that the issuer of this token was ZoneMinder,
It was issued at (iat) Wednesday, 2019-05-15 17:19:12 UTC and will expire on (exp) Wednesday, 2019-05-15 18:19:12 UTC. This token claims to be owned by an admin and is an access token. If your token were to be stolen, this information is available to the person who stole it. Note that there are no sensitive details like passwords in this claim.

However, that person will **not** have your secret key as part of this token and therefore, will NOT be able to create a new JWT token to get, say, a refresh token. They will however, be able to use your access token to access resources just like the auth hash above, till the access token expires (2 hrs). To revoke this token, you don't need to disable the user. Go to ``Options->API`` and tap on "Revoke All Access Tokens". This will invalidate the token immediately (this option will invalidate all tokens for all users, and new ones will need to be generated).

Over time, we will provide you with more fine grained access to these options.

**Summarizing good practices:** 

* Use HTTPS, not HTTP
* If possible, use free services like `LetsEncrypt <https://letsencrypt.org>`__ instead of self-signed certificates (sometimes this is not possible)
* Keep your tokens as private as possible, and use them as recommended above
* If you believe your tokens are compromised, revoke them, but also check if your attacker has compromised more than you think (example, they may also have your username/password or access to your system via other exploits, in which case they can regenerate as many tokens/credentials as they want).


.. NOTE::
	Subsequent sections don't explicitly callout the key addition to APIs. We assume that you will append the correct keys as per our explanation above.


Examples 
^^^^^^^^^

(In all examples, replace 'server' with IP or hostname & port where ZoneMinder is running)

API Version
^^^^^^^^^^^
To retrieve the API version:

::

  curl http://server/zm/api/host/getVersion.json


Return a list of all monitors
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::
  
	curl http://server/zm/api/monitors.json

It is worthwhile to note that starting ZM 1.32.3 and beyond, this API also returns a ``Monitor_Status`` object per monitor. It looks like this:

::

        "Monitor_Status": {
                "MonitorId": "2",
                "Status": "Connected",
                "CaptureFPS": "1.67",
                "AnalysisFPS": "1.67",
                "CaptureBandwidth": "52095"
            }


If you don't see this in your API, you are running an older version of ZM. This gives you a very convenient way to check monitor status without calling the ``daemonCheck`` API described later.


Retrieve monitor 1
^^^^^^^^^^^^^^^^^^^

::
  
  	curl http://server/zm/api/monitors/1.json


Change State of Monitor 1
^^^^^^^^^^^^^^^^^^^^^^^^^^

This API changes monitor 1 to Modect and Enabled

::

  curl -XPOST http://server/zm/api/monitors/1.json -d "Monitor[Function]=Modect&Monitor[Enabled]=1"
  
Get Daemon Status of Monitor 1
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  	curl http://server/zm/api/monitors/daemonStatus/id:1/daemon:zmc.json

Add a monitor
^^^^^^^^^^^^^^

This command will add a new http monitor.

::

  curl -XPOST http://server/zm/api/monitors.json -d "Monitor[Name]=Cliff-Burton\
  &Monitor[Function]=Modect\
  &Monitor[Protocol]=http\
  &Monitor[Method]=simple\
  &Monitor[Host]=usr:pass@192.168.11.20\
  &Monitor[Port]=80\
  &Monitor[Path]=/mjpg/video.mjpg\
  &Monitor[Width]=704\
  &Monitor[Height]=480\
  &Monitor[Colours]=4"

Edit monitor 1
^^^^^^^^^^^^^^^

This command will change the 'Name' field of Monitor 1 to 'test1'

::

  curl -XPUT http://server/zm/api/monitors/1.json -d "Monitor[Name]=test1"


Delete monitor 1
^^^^^^^^^^^^^^^^^

This command will delete Monitor 1, but will _not_ delete any Events which
depend on it.

::

  curl -XDELETE http://server/zm/api/monitors/1.json


Arm/Disarm monitors
^^^^^^^^^^^^^^^^^^^^

This command will force an alarm on Monitor 1:

::

  curl http://server/zm/api/monitors/alarm/id:1/command:on.json

This command will disable the  alarm on Monitor 1:

::

  curl http://server/zm/api/monitors/alarm/id:1/command:off.json

This command will report the status of the alarm  Monitor 1:

::

  curl http://server/zm/api/monitors/alarm/id:1/command:status.json


Return a list of all events
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  http://server/zm/api/events.json


Note that events list can be quite large and this API (as with all other APIs in ZM)
uses pagination. Each page returns a specific set of entries. By default this is 25
and ties into WEB_EVENTS_PER_PAGE in the ZM options menu. 

So the logic to iterate through all events should be something like this (pseudocode):
(unfortunately there is no way to get pageCount without getting the first page)

::

  data = http://server/zm/api/events.json?page=1 # this returns the first page
  # The json object returned now has a property called data.pagination.pageCount
  count = data.pagination.pageCount;
  for (i=1, i<count, i++)
  {
    data = http://server/zm/api/events.json?page=i;
     doStuff(data);
  }


Retrieve event Id 1000
^^^^^^^^^^^^^^^^^^^^^^

::

  curl -XGET http://server/zm/api/events/1000.json


Edit event 1
^^^^^^^^^^^^^

This command will change the 'Name' field of Event 1 to 'Seek and Destroy'

::

  curl -XPUT http://server/zm/api/events/1.json -d "Event[Name]=Seek and Destroy"

Delete event 1
^^^^^^^^^^^^^^
This command will delete Event 1, and any Frames which depend on it.

::

  curl -XDELETE http://server/zm/api/events/1.json


Return a list of events for a specific monitor Id =5
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
::

  curl -XGET http://server/zm/api/events/index/MonitorId:5.json


Note that the same pagination logic applies if the list is too long


Return a list of events for a specific monitor within a specific date/time range
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  http://server/zm/api/events/index/MonitorId:5/StartTime >=:2015-05-15 18:43:56/EndTime <=:2015-05-16 18:43:56.json


To try this in CuRL, you need to URL escape the spaces like so:

::

  curl -XGET  "http://server/zm/api/events/index/MonitorId:5/StartTime%20>=:2015-05-15%2018:43:56/EndTime%20<=:2015-05-16%2018:43:56.json"


Return a list of events for all monitors within a specified date/time range
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  curl -XGET "http://server/zm/api/events/index/StartTime%20>=:2015-05-15%2018:43:56/EndTime%20<=:208:43:56.json"


Return event count based on times and conditions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The API also supports a handy mechanism to return a count of events for a period of time.

This returns number of events per monitor that were recorded in the last one hour

::

  curl "http://server/zm/api/events/consoleEvents/1%20hour.json"

This returns number of events per monitor that were recorded in the last day where there were atleast 10 frames that were alarms"

::

  curl "http://server/zm/api/events/consoleEvents/1%20day.json/AlarmFrames >=: 10.json"




Return sorted events
^^^^^^^^^^^^^^^^^^^^^^

This returns a list of events within a time range and also sorts it by descending order

::

  curl -XGET "http://server/zm/api/events/index/StartTime%20>=:2015-05-15%2018:43:56/EndTime%20<=:208:43:56.json?sort=StartTime&direction=desc"


Configuration Apis
^^^^^^^^^^^^^^^^^^^

The APIs allow you to access all the configuration parameters of ZM that you typically set inside the web console.
This returns the full list of configuration parameters:

::

  curl -XGET http://server/zm/api/configs.json


Each configuration parameter has an Id, Name, Value and other fields. Chances are you are likely only going to focus on these 3.

The edit function of the Configs API is a little quirky at the moment. Its format deviates from the usual edit flow of other APIs. This will be fixed, eventually. For now, to change the "Value" of ZM_X10_HOUSE_CODE from A to B:

::

    curl -XPUT http://server/zm/api/configs/edit/ZM_X10_HOUSE_CODE.json  -d "Config[Value]=B"

To validate changes have been made:

::

    curl -XGET http://server/zm/api/configs/view/ZM_X10_HOUSE_CODE.json 

Run State Apis
^^^^^^^^^^^^^^^

ZM API can be used to start/stop/restart/list states of  ZM as well
Examples:

::

  curl -XGET  http://server/zm/api/states.json # returns list of run states
  curl -XPOST  http://server/zm/api/states/change/restart.json #restarts ZM
  curl -XPOST  http://server/zm/api/states/change/stop.json #Stops ZM
  curl -XPOST  http://server/zm/api/states/change/start.json #Starts ZM



Create a Zone
^^^^^^^^^^^^^^

::

  curl -XPOST http://server/zm/api/zones.json -d "Zone[Name]=Jason-Newsted\
  &Zone[MonitorId]=3\
  &Zone[Type]=Active\
  &Zone[Units]=Percent\
  &Zone[NumCoords]=4\
  &Zone[Coords]=0,0 639,0 639,479 0,479\
  &Zone[Area]=307200\
  &Zone[AlarmRGB]=16711680\
  &Zone[CheckMethod]=Blobs\
  &Zone[MinPixelThreshold]=25\
  &Zone[MaxPixelThreshold]=\
  &Zone[MinAlarmPixels]=9216\
  &Zone[MaxAlarmPixels]=\
  &Zone[FilterX]=3\
  &Zone[FilterY]=3\
  &Zone[MinFilterPixels]=9216\
  &Zone[MaxFilterPixels]=230400\
  &Zone[MinBlobPixels]=6144\
  &Zone[MaxBlobPixels]=\
  &Zone[MinBlobs]=1\
  &Zone[MaxBlobs]=\
  &Zone[OverloadFrames]=0"

PTZ Control Meta-Data APIs
^^^^^^^^^^^^^^^^^^^^^^^^^^^
PTZ controls associated with a monitor are stored in the Controls table and not the Monitors table inside ZM. What that means is when you get the details of a Monitor, you will only know if it is controllable (isControllable:true) and the control ID.
To be able to retrieve PTZ information related to that Control ID, you need to use the controls API

Note that these APIs only retrieve control data related to PTZ. They don't actually move the camera. See the "PTZ on live streams" section to move the camera.

This returns all the control definitions:
::

  curl http://server/zm/api/controls.json

This returns control definitions for a specific control ID=5
::
  
  curl http://server/zm/api/controls/5.json

Host APIs
^^^^^^^^^^

ZM APIs have various APIs that help you in determining host (aka ZM) daemon status, load etc. Some examples:

::

  curl -XGET  http://server/zm/api/host/getLoad.json # returns current load of ZM

  # Note that ZM 1.32.3 onwards has the same information in Monitors.json which is more reliable and works for multi-server too.
  curl -XGET  http://server/zm/api/host/daemonCheck.json # 1 = ZM running 0=not running

  # The API below uses "du" to calculate disk space. We no longer recommend you use it if you have many events. Use the Storage APIs instead, described later
  curl -XGET  http://server/zm/api/host/getDiskPercent.json # returns in GB (not percentage), disk usage per monitor (that is,space taken to store various event related information,images etc. per monitor)


Storage and Server APIs
^^^^^^^^^^^^^^^^^^^^^^^

ZoneMinder introduced many new options that allowed you to configure multiserver/multistorage configurations. While a part of this was available in previous versions, a lot of rework was done as part of ZM 1.31 and 1.32. As part of that work, a lot of new and useful APIs were added. Some of these are part of ZM 1.32 and others will be part of ZM 1.32.3 (of course, if you build from master, you can access them right away, or wait till a stable release is out.



This returns storage data for my single server install. If you are using multi-storage, you'll see many such "Storage" entries, one for each storage defined:

::

        curl http://server/zm/api/storage.json

Returns:

::

        {
            "storage": [
                {
                    "Storage": {
                        "Id": "0",
                        "Path": "\/var\/cache\/zoneminder\/events",
                        "Name": "Default",
                        "Type": "local",
                        "Url": null,
                        "DiskSpace": "364705447651",
                        "Scheme": "Medium",
                        "ServerId": null,
                        "DoDelete": true
                    }
                 }
               ]
        }



"DiskSpace" is the disk used in bytes. While this doesn't return disk space data as rich as  ``/host/getDiskPercent``, it is much more efficient.

Similarly, 

::

        curl http://server/zm/api/servers.json 

Returns:

::

      {
            "servers": [
                {
                    "Server": {
                        "Id": "1",
                        "Name": "server1",
                        "Hostname": "server1.mydomain.com",
                        "State_Id": null,
                        "Status": "Running",
                        "CpuLoad": "0.9",
                        "TotalMem": "6186237952",
                        "FreeMem": "156102656",
                        "TotalSwap": "536866816",
                        "FreeSwap": "525697024",
                        "zmstats": false,
                        "zmaudit": false,
                        "zmtrigger": false
                    }
                }
            ]
        }

This only works if you have a multiserver setup in place. If you don't it will return an empty array.

Other APIs
^^^^^^^^^^
This is not a complete list. ZM supports more parameters/APIs. A good way to dive in is to look at the `API code <https://github.com/ZoneMinder/zoneminder/tree/master/web/api/app/Controller>`__ directly. 

Streaming Interface
^^^^^^^^^^^^^^^^^^^
Developers working on their application often ask if there is an "API" to receive live streams, or recorded event streams.
It is possible to stream both live and recorded streams. This isn't strictly an "API" per-se (that is, it is not integrated
into the Cake PHP based API layer discussed here) and also why we've used the term "Interface" instead of an "API".

Live Streams
~~~~~~~~~~~~~~
What you need to know is that if you want to display "live streams", ZoneMinder sends you streaming JPEG images (MJPEG)
which can easily be rendered in a browser using an ``img src`` tag.

For example:

::

    <img src="https://yourserver/zm/cgi-bin/nph-zms?scale=50&width=640p&height=480px&mode=jpeg&maxfps=5&buffer=1000&&monitor=1&token=eW<deleted>03&connkey=36139" />

    # or 

    <img src="https://yourserver/zm/cgi-bin/nph-zms?scale=50&width=640p&height=480px&mode=jpeg&maxfps=5&buffer=1000&&monitor=1&auth=b5<deleted>03&connkey=36139" />
    



will display a live feed from monitor id 1, scaled down by 50% in quality and resized to 640x480px. 

* This assumes ``/zm/cgi-bin`` is your CGI_BIN path. Change it to what is correct in your system
* The "auth" token you see above is required if you use ZoneMinder authentication. To understand how to get the auth token, please read the "Login, Logout & API security" section below.
* The "connkey" parameter is essentially a random number which uniquely identifies a stream. If you don't specify a connkey, ZM will generate its own. It is recommended to generate a connkey because you can then use it to "control" the stream (pause/resume etc.)
* Instead of dealing with the "auth" token, you can also use ``&user=username&pass=password`` where "username" and "password" are your ZoneMinder username and password respectively. Note that this is not recommended because you are transmitting them in a URL and even if you use HTTPS, they may show up in web server logs.


PTZ on live streams
-------------------
PTZ commands are pretty cryptic in ZoneMinder. This is not meant to be an exhaustive guide, but just something to whet your appetite:


Lets assume you have a monitor, with ID=6. Let's further assume you want to pan it left.

You'd need to send a:
``POST`` command to ``https://yourserver/zm/index.php`` with the following data payload in the command (NOT in the URL)

``view=request&request=control&id=6&control=moveConLeft&xge=30&yge=30``

Obviously, if you are using authentication, you need to be logged in for this to work.

Like I said, at this stage, this is only meant to get you started. Explore the ZoneMinder code and use "Inspect source" as you use PTZ commands in the ZoneMinder source code.
`control_functions.php <https://github.com/ZoneMinder/zoneminder/blob/10531df54312f52f0f32adec3d4720c063897b62/web/skins/classic/includes/control_functions.php>`__ is a great place to start.


Pre-recorded (past event) streams
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Similar to live playback, if you have chosen to store events in JPEG mode, you can play it back using:

::

    <img src="https://yourserver/zm/cgi-bin/nph-zms?mode=jpeg&frame=1&replay=none&source=event&event=293820&connkey=77493&token=ew<deleted>" />

    # or 

    <img src="https://yourserver/zm/cgi-bin/nph-zms?mode=jpeg&frame=1&replay=none&source=event&event=293820&connkey=77493&auth=b5<deleted>" />



* This assumes ``/zm/cgi-bin`` is your CGI_BIN path. Change it to what is correct in your system
* This will playback event 293820, starting from frame 1 as an MJPEG stream
* Like before, you can add more parameters like ``scale`` etc. 
* auth and connkey have the same meaning as before, and yes, you can replace auth by ``&user=usename&pass=password`` as before and the same security concerns cited above apply.

If instead, you have chosen to use the MP4 (Video) storage mode for events, you can directly play back the saved video file:

::

   
    <video src="https://yourserver/zm/index.php?view=view_video&eid=294690&token=eW<deleted>" type="video/mp4"></video>

    # or 

    <video src="https://yourserver/zm/index.php?view=view_video&eid=294690&auth=33<deleted>" type="video/mp4"></video>
   

This above will play back the video recording for event 294690

What other parameters are supported?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
The best way to answer this question is to play with ZoneMinder console. Open a browser, play back live or recorded feed, and do an "Inspect Source" to see what parameters 
are generated. Change and observe.



Further Reading
^^^^^^^^^^^^^^^^

As described earlier, treat this document as an "introduction" to the important parts of the API and streaming interfaces.
There are several details that haven't yet been documented. Till they are, here are some resources:

* zmNinja, the open source mobile app for ZoneMinder is 100% based on ZM APIs. Explore its `source code <https://github.com/pliablepixels/zmNinja>`__ to see how things work.
* Launch up ZM console in a browser, and do an "Inspect source". See how images are being rendered. Go to the networks tab of the inspect source console and look at network requests that are made when you pause/play/forward streams.
* If you still can't find an answer, post your question in the `forums <https://forums.zoneminder.com/index.php>`__ (not the github repo).




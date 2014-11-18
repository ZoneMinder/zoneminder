Options - System
================

.. image:: images/Options_System.png

* LANG_DEFAULT - Defines default language used by the web interface
* OPT_USE_AUTH - Turn on ZoneMinder user authentication
* AUTH_TYPE - What process is used to authenticate ZoneMinder users

    - Built-in
    - Remote

* AUTH_RELAY - Method used to relay authentication information
* AUTH_HASH_SECRET - Secret for encoding hashed authentication information
* AUTH_HASH_IPS - Include IP addresses in hashed authentication
* AUTH_HASH_LOGINS - Allow login by authentication hash
* OPT_FAST_DELETE - Delete only event database records for faster deletion
* FILTER_RELOAD_DELAY - How often in seconds filters are reloaded in zmfilter
* FILTER_EXECUTE_INTERVAL - How often automatic saved filters are run
* MAX_RESTART_DELAY - Maximum delay in seconds for daemon restart attempts
* WATCH_CHECK_INTERVAL	- How often to check daemons have not locked up
* WATCH_MAX_DELAY - The maximum delay allowed since the last captured image
* RUN_AUDIT - Run zmaudit to check data consistency
* AUDIT_CHECK_INTERVAL - How often to run zmaudit to check database and filesystem consistency
* OPT_FRAME_SERVER - Starts frame server to process images instead of zma.
* FRAME_SOCKET_SIZE - Specify the frame server socket buffer size if non-standard
* OPT_CONTROL - Support PTZ cameras through control scripts
* OPT_TRIGGERS - Support external event triggers via socket ot device files
* CHECK_FOR_UPDATES - Check with ZoneMinder.com for updated versions
* UPDATE_CHECK_PROXY - Proxy URL is required for update check process.
* SHM_KEY - Share memory root key to use


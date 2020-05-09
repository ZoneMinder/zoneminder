ZoneMinder API
==============

This is the ZoneMinder API.  It should be, for now, installed under the webroot
e.g. /api.

app/Config/database.php.default must be configured and copied to
app/Config/database.php

In adition, Security.salt and Security.cipherSeed in app/Config/core.php should
be changed.

The API can run on a dedicated / separate instance, so long as it can access
the database as configured in app/Config/database.php

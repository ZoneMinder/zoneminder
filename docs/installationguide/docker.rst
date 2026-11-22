Docker
======

.. contents::

The ZoneMinder project publishes Docker images built from the same zmrepo packages as a normal
install. They run on amd64 and arm64. The compose setup, the full list of settings and the issue
tracker are in the `zmdockerfiles <https://github.com/ZoneMinder/zmdockerfiles>`__ repo. This
page covers what you need to get running.

Images and tags
---------------

The images are on `Docker Hub <https://hub.docker.com/r/zoneminder/zoneminder>`__ as
``zoneminder/zoneminder``, and on the
`GitHub Container Registry <https://github.com/ZoneMinder/zmdockerfiles/pkgs/container/zoneminder>`__
as ``ghcr.io/zoneminder/zoneminder``, which has no pull limits. Both carry the same tags:

``latest``
    The newest release. It moves to each new minor release (1.40, 1.42) when zmrepo publishes it.

``1.38``, ``1.38.6`` and so on
    Pin a release series or an exact version, if you want to choose when to upgrade.

``dev``
    Nightly builds of master. Don't run them on a system you rely on.

Each tag has an ``-es`` version, for example ``latest-es``, that adds the event server
(`zmeventnotificationNg <https://github.com/ZoneMinder/zmeventnotificationNg>`__).

CI rebuilds every image daily, so a ``docker compose pull`` picks up Debian security fixes as
well as new ZoneMinder releases.

Quick start
-----------

You need Docker with the compose plugin. The compose file runs ZoneMinder and MariaDB in
separate containers.

::

    git clone https://github.com/ZoneMinder/zmdockerfiles.git
    cd zmdockerfiles
    cp .env.example .env     # set ZM_DB_PASS, MARIADB_ROOT_PASSWORD and TZ
    docker compose up -d

Open ``http://localhost:8080/zm``. On first start the container creates the database schema. On
later starts it runs ``zmupdate.pl`` to upgrade the schema when the image has a newer ZoneMinder.

Then finish ZoneMinder's own first-run setup, as on any other install:

- 1.38 starts with authentication off. Turn on ``OPT_USE_AUTH`` under Options > Authentication, log in
  as ``admin`` / ``admin``, and change that password under Options > Users.
- 1.39 and later (the ``dev`` tags) start with authentication on. The first page asks you to set
  the ``admin`` password or turn authentication off. See :ref:`first_run`.
- Answer the usage statistics prompt. Until you do, the web UI and image URLs redirect to it.

The container writes only the database settings to ``/etc/zm/conf.d/03-docker.conf``. Everything
else is set in the web UI.

``docker compose ps`` shows whether the container is healthy. It reports unhealthy when the web
server stops answering, the ``zmdc.pl`` daemon is gone, or the database doesn't answer.

Storage and ports
-----------------

``/var/cache/zoneminder``
    Events, images and the cache. This is the volume that grows, so put it on a disk with room.

``/config``
    ``go2rtc.yaml``, the event server config and its certificates.

``/var/log/zm``
    ZoneMinder's logs. The main ones also go to ``docker compose logs``.

``/dev/shm``
    A tmpfs for each monitor's frame buffer, 1 GiB by default. Raise ``ZM_SHM_SIZE`` in ``.env``
    for many or high resolution monitors.

The web UI is on port 80 in the container, published as 8080. WebRTC uses 8555 (TCP and UDP),
and the event server uses 9000.

If your volumes are bind mounts owned by a user on the host, set ``PUID`` and ``PGID`` to that
user's IDs. The container runs ZoneMinder, Apache and go2rtc as that user.

Live view with go2rtc
---------------------

The image includes go2rtc for WebRTC and MSE live view, served under ZoneMinder's own address at
``/zm/go2rtc/``. To use it:

1. Set ``ZM_PUBLIC_URL`` in ``.env`` to the address you open ZoneMinder at, using a hostname, for
   example ``http://zm.lan:8080``.
2. Set Options > System > ``GO2RTC_PATH`` to that address plus ``/zm/go2rtc``, so
   ``http://zm.lan:8080/zm/go2rtc``.
3. Turn on go2rtc for each monitor.

Use a hostname rather than an IP address. ZoneMinder registers streams with go2rtc from inside the
container, and with an IP address those calls arrive through the host and are refused. Behind an
https reverse proxy, also set ``GO2RTC_API_ALLOW`` to the proxy's address.

.. warning::

   go2rtc live streams are not protected by ZoneMinder's login. Anyone who can reach the web port
   can watch any monitor with go2rtc turned on. Keep the port on a network you trust, or put an
   authenticating reverse proxy or a VPN in front of it. Monitors without go2rtc use ZoneMinder's
   own streaming, which does check the login.

   The container refuses the rest of go2rtc's API from outside, including its config and its
   web page, so the exposure is limited to watching streams.

Hardware decoding
-----------------

Uncomment the ``devices:`` block in ``docker-compose.yaml`` to pass ``/dev/dri`` into the
container, then set DecoderHWAccelName and DecoderHWAccelDevice on the monitor. The image ships
the Mesa VA-API drivers, plus Intel's media driver on amd64. To see what the container can use:

::

    docker compose exec zoneminder vainfo

The container adds its user to the groups that own any devices you pass through, so no
``privileged`` mode is needed.

Event server and object detection
---------------------------------

The event server reads ZoneMinder's shared memory, so it runs inside the ZoneMinder container
(the ``-es`` images). Object detection runs in a separate ``pyzm-serve`` container, which the
event server's hook sends frames to over HTTP.

::

    # push notifications, WebSocket (port 9000) and MQTT, no detection
    ZM_TAG=latest-es docker compose up -d

    # plus object detection
    ZM_TAG=latest-es ZMES_USE_HOOKS=yes docker compose --profile ml up -d

On first start the container copies the event server config to ``/config/es/``. Edit the files
there and restart. Once ZoneMinder's authentication is on, put your login in
``/config/es/secrets.yml`` as ``ZM_USER`` and ``ZM_PASSWORD``.

``pyzm-serve`` also has a ``cuda`` tag that runs detection on an NVIDIA GPU (amd64 only). It
needs the NVIDIA Container Toolkit on the host. See the zmdockerfiles README for the GPU setup,
and the `event server documentation <https://zmeventnotification.readthedocs.io/en/latest/index.html>`__ for what the hook can do.

Upgrading from the old images
-----------------------------

The old ``zoneminderhq/zoneminder`` images, last published in 2021, ran MySQL inside the
ZoneMinder container. To move to the new setup, dump the database from the old container and
load it into the new one:

::

    docker exec old-zoneminder mysqldump --single-transaction --routines --triggers zm > zm.sql
    docker compose up -d --wait db
    docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" zm' < zm.sql
    docker compose up -d

``old-zoneminder`` is the old container's name. ``zmupdate.pl`` upgrades the schema on first
start. Events are in the same place (``/var/cache/zoneminder/events``), so point the events
volume at the old directory or bind mount it.

Verifying images
----------------

CI signs every image with `cosign <https://docs.sigstore.dev/>`__ and attaches an SBOM listing
every package inside it. To check that an image came from the zmdockerfiles master branch:

::

    cosign verify zoneminder/zoneminder:latest \
      --certificate-oidc-issuer https://token.actions.githubusercontent.com \
      --certificate-identity-regexp '^https://github\.com/ZoneMinder/zmdockerfiles/\.github/workflows/(images|pyzm-cuda)\.yml@refs/heads/master$'

Getting help
------------

Ask questions on the `forums <https://forums.zoneminder.com/>`__. Report bugs in the images at
`zmdockerfiles issues <https://github.com/ZoneMinder/zmdockerfiles/issues>`__, with your compose
file (passwords removed) and the output of ``docker compose logs zoneminder``.

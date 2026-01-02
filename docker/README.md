# ZoneMinder Docker (local only)

This folder provides a Docker/OCI setup for ZoneMinder using Ubuntu 24.04 packages from zmrepo release-1.36. 

## Key rules

- The ZoneMinder container does not run MariaDB/MySQL; the database is external.
- The entrypoint waits for the DB, initializes the schema if needed, and runs `zmupdate.pl` non-interactively.

## Local build/run

```sh
cd docker/compose
cp .env.example .env
docker compose -f docker-compose.local.yml up --build
```

Open http://localhost:8080

## Logs and troubleshooting

```sh
docker compose -f docker-compose.local.yml logs -f zoneminder
docker compose -f docker-compose.local.yml exec zoneminder bash
docker compose -f docker-compose.local.yml exec db mariadb -u root -p
```

## Rebuild

```sh
docker compose -f docker-compose.local.yml build --no-cache zoneminder
```

## Optional smoke check

```sh
./smoke.sh
```

FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://zmrepo.zoneminder.com/debian/archive-keyring.gpg \
    | gpg --dearmor -o /etc/apt/trusted.gpg.d/zoneminder.gpg \
    && echo "deb https://zmrepo.zoneminder.com/debian/release-1.36 noble/" \
      > /etc/apt/sources.list.d/zoneminder.list

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
      zoneminder \
      mariadb-client \
    && rm -rf /var/lib/apt/lists/*

COPY apache-site.conf /etc/apache2/sites-available/zoneminder.conf

RUN a2enmod rewrite cgi headers \
    && if [ -f /etc/apache2/conf-available/zoneminder.conf ]; then a2enconf zoneminder; fi \
    && a2dissite 000-default \
    && a2ensite zoneminder

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

#!/bin/bash

set -ev
set -o pipefail

with_timestamps() {
	while read -r line; do
	echo -e "$(date +%T)\t$line";
	done
}

run_tests() {
	mysql -uzmuser -pzmpass < ${TRAVIS_BUILD_DIR}/db/zm_create.sql
	mysql -uzmuser -pzmpass zm < ${TRAVIS_BUILD_DIR}/db/test.monitor.sql
	sudo zmu -l
	sudo zmc -m1 &
	sudo zma -m1 &
	sudo zmu -l
	sudo grep ERR /var/log/syslog
	sudo zmpkg.pl start
	sudo zmfilter.pl -f purgewhenfull
}

run_tests | with_timestamps

#!/bin/bash

set -e
set -o pipefail

with_timestamps() {
	while read -r line; do
	echo -e "$(date +%T)\t$line";
	done
}

run_tests() {
	mysql -uzmuser -pzmpass zm < ../../db/test.monitor.sql
	sudo zmu -l
	sudo zmc -m1 &
	sudo zma -m1 &
	sudo zmu -l
	sudo grep ERR /var/log/syslog
	sudo zmpkg.pl start
	sudo zmfilter.pl -f purgewhenfull
}

run_tests | with_timestamps

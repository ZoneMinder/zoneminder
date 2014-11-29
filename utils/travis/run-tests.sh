#!/bin/bash

set -e
set -o pipefail

with_timestamps() {
	while read -r line; do
	echo -e "$(date +%T)\t$line";
	done
}

run_tests() {
	mysql -uzmuser -pzmpass < ${TRAVIS_BUILD_DIR}/db/zm_create.sql
	mysql -uzmuser -pzmpass zm < ${TRAVIS_BUILD_DIR}/db/test.monitor.sql
	sudo zmpkg.pl start
	sudo zmfilter.pl -f purgewhenfull
	sudo cp -f utils/travis/apache-vhost /etc/apache2/sites-enabled/000-default
	sudo service apache2 restart
	npm install -g se-interpreter
	se-interpreter utils/tests/interpreter_config.json

}

run_tests | with_timestamps

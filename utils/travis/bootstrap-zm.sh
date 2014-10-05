#!/bin/bash -x

set -ev
set -o pipefail

with_timestamps() {
	while read -r line; do
	echo -e "$(date +%T)\t$line";
	done
}


bootstrap_zm() {

	if [ "$ZM_BUILDMETHOD" = "autotools" ]; then libtoolize --force; fi
	if [ "$ZM_BUILDMETHOD" = "autotools" ]; then aclocal; fi
	if [ "$ZM_BUILDMETHOD" = "autotools" ]; then autoheader; fi
	if [ "$ZM_BUILDMETHOD" = "autotools" ]; then automake --force-missing --add-missing; fi
	if [ "$ZM_BUILDMETHOD" = "autotools" ]; then autoconf; fi

	mysql -uroot -e "CREATE DATABASE IF NOT EXISTS zm"
	mysql -uroot -e "GRANT ALL ON zm.* TO 'zmuser'@'localhost' IDENTIFIED BY 'zmpass'";
	mysql -uroot -e "FLUSH PRIVILEGES"

}

bootstrap_zm | with_timestamps

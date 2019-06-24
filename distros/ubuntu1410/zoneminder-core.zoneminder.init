#!/bin/sh
### BEGIN INIT INFO
# Provides:          zoneminder
# Required-Start:    $network $remote_fs $syslog
# Required-Stop:     $network $remote_fs $syslog
# Should-Start:      mysql
# Should-Stop:       mysql
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description:  Control ZoneMinder as a Service
### END INIT INFO
# description: Control ZoneMinder as a Service
# chkconfig: 2345 20 20

# Source function library.
#. /etc/rc.d/init.d/functions

prog=ZoneMinder
ZM_PATH_BIN="/usr/bin"
RUNDIR=/var/run/zm
TMPDIR=/tmp/zm
command="$ZM_PATH_BIN/zmpkg.pl"

start() {
	echo -n "Starting $prog: "
	mkdir -p $RUNDIR && chown www-data:www-data $RUNDIR
	mkdir -p $TMPDIR && chown www-data:www-data $TMPDIR
	$command start
	RETVAL=$?
	[ $RETVAL = 0 ] && echo success
	[ $RETVAL != 0 ] && echo failure
	echo
	[ $RETVAL = 0 ] && touch /var/lock/zm
	return $RETVAL
}
stop() {
	echo -n "Stopping $prog: "
	#
	# Why is this status check being done?
	# as $command stop returns 1 if zoneminder 
	# is stopped, which will result in 
	# this returning 1, which will stuff 
	# dpkg when it tries to stop zoneminder before
	# uninstalling . . . 
	#
	result=`$command status`
	if [ ! "$result" = "running" ]; then
		echo "Zoneminder already stopped"
		echo
		RETVAL=0
	else
		$command stop
		RETVAL=$?
		[ $RETVAL = 0 ] && echo success
		[ $RETVAL != 0 ] && echo failure
		echo
		[ $RETVAL = 0 ] && rm -f /var/lock/zm
	fi
}
status() {
	result=`$command status`
	if [ "$result" = "running" ]; then
		echo "ZoneMinder is running"
		RETVAL=0
	else
		echo "ZoneMinder is stopped"
		RETVAL=1
	fi
}

case "$1" in
'start')
	start
	;;
'stop')
	stop
	;;
'restart' | 'force-reload')
	stop
	start
	;;
'status')
	status
	;;
*)
	echo "Usage: $0 { start | stop | restart | status }"
	RETVAL=1
	;;
esac
exit $RETVAL

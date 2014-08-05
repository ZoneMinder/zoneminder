AUTOMAKE_OPTIONS = gnu

EXTRA_DIST = \
	apache.conf.in \
	logrotate.conf.in \
	syslog.conf.in

if SYSTEMD
EXTRA_DIST += \
	com.zoneminder.systemctl.policy.in \
	com.zoneminder.systemctl.rules.in

polkit_actiondir = /usr/share/polkit-1/actions
dist_polkit_action_DATA = com.zoneminder.systemctl.policy

polkit_rulesdir = /usr/share/polkit-1/rules.d
dist_polkit_rules_DATA = com.zoneminder.systemctl.rules
endif


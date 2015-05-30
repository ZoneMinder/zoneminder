AUTOMAKE_OPTIONS = gnu

EXTRA_DIST = \
	apache.conf.in \
	logrotate.conf.in \
	syslog.conf.in \
	zoneminder-tmpfiles.conf.in \
	zoneminder.service.in \
	com.zoneminder.systemctl.policy.in \
	com.zoneminder.systemctl.rules.in

if ZM_HAS_PLUGIN_SUPPORT
EXTRA_DIST += plugins.conf.in

# Install a pkg-config file for a proper handling of the libzm
# convenience library
pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = libzoneminder.pc

sysconf_DATA = plugins.conf
endif

polkit_actiondir = @POLKIT_PREFIX@/share/polkit-1/actions
dist_polkit_action_DATA = com.zoneminder.systemctl.policy

polkit_rulesdir = @POLKIT_PREFIX@/share/polkit-1/rules.d
dist_polkit_rules_DATA = com.zoneminder.systemctl.rules

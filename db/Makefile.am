AUTOMAKE_OPTIONS = foreign

zmdbdatadir = $(pkgdatadir)/db

EXTRA_DIST = \
	zm_create.sql.in \
	$(dbupgrade_scripts)

dist_zmdbdata_DATA = \
	zm_create.sql \
	$(dbupgrade_scripts)

dbupgrade_scripts = $(wildcard zm_update-*.sql)


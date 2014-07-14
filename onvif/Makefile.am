AUTOMAKE_OPTIONS = gnu

# Ack! Nasty hack to get modules Makefile regenerated if wiped with make clean
all-local: proxy/Makefile modules/Makefile

proxy/Makefile: proxy/Makefile.PL
	( cd proxy; perl Makefile.PL )

modules/Makefile: modules/Makefile.PL
	( cd modules; perl Makefile.PL )

SUBDIRS = \
 	. \
  proxy \
  modules \
  scripts 
   
   
EXTRA_DIST = \
  proxy/Makefile.PL \
  proxy/lib \
  modules/Makefile.PL \
  modules/lib \
  wsdl \
  doc \
  patches

#/***********************************************************************
#    This file is part of libzm_anpr_plugin, License Plate REcognition.
#
#    Copyright (C) 2012 Franco (nextime) Lanza <nextime@nexlab.it>
#
#    LiPRec is free software: you can redistribute it and/or modify
#    it under the terms of the GNU Affero General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    LiPRec is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU Affero General Public License for more details.
#
#    You should have received a copy of the GNU Affero General Public License
#    along with LiPRec.  If not, see <http://www.gnu.org/licenses/>.
#************************************************************************/


OBJECTS = anpr_plugin.o 
SOBJ=anpr_plugin.so

ZMSRC = zm_detector.cpp zm_config.cpp zm_poly.cpp zm_zone.cpp zm_image.cpp zm_db.cpp
ZMSRC+= zm_logger.cpp zm_utils.cpp zm_thread.cpp zm_jpeg.cpp
ZMOBJ = $(ZMSRC:.cpp=.o)

#CPPFLAGS= -fpermissive -O3 -march=native -Wall -lliprec -msse2
CPPFLAGS= -fpermissive -O3 -Wall -lliprec -msse2
CPPFLAGS+=-D__STDC_CONSTANT_MACROS -DZM_FFMPEG_CVS -DHAVE_LIBCRYPTO 
CPPFLAGS+=-DJPEG_INCLUDE_DIR=../../../../../libjpeg-turbo-1.2.1/.libs/
LDFLAGS=-L. -L../../../../../libjpeg-turbo-1.2.1/.libs/ 
LDFLAGS+=-I../../src/ -I../../ -I./
LDFLAGS+=-L/usr/lib  -I/usr/include -I../../../../../libjpeg-turbo-1.2.1/
LDFLAGS+=$(shell pkg-config --cflags opencv)

LINKFLAGS=$(shell pkg-config --libs opencv)
LINKFLAGS+= -lliprec -lboost_program_options -lmysqlclient -lpthread -ljpeg -lz


all: anpr_plugin.o $(SOBJ)


$(ZMOBJ): %.o : ../../src/%.cpp
	$(CXX) $^ -fPIC -c -o $@ ${LDFLAGS} $(CPPFLAGS)

anpr_plugin.o: anpr_plugin.cpp
	$(CXX) anpr_plugin.cpp  -fPIC -c -o anpr_plugin.o ${LDFLAGS} $(CPPFLAGS)

anpr_plugin.so: $(ZMOBJ) anpr_plugin.o
	$(CXX) -o anpr_plugin.so -Wall anpr_plugin.o -shared $(ZMOBJ) $(LINKFLAGS)
	cp anpr_plugin.so libzm_plugin_anpr.zmpl

install: anpr_plugin.o $(SOBJ)
	install -m 0644 libzm_plugin_anpr.zmpl /usr/share/zm/libzm_plugin_anpr.zmpl
	$(shell cat plugin.conf >> /usr/share/zm/plugin.conf)

clean: 
	rm -f $(OBJECTS) $(ZMOBJ) $(SOBJ) libzm_plugin_anpr.zmpl


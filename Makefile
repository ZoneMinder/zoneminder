all: zmc zma zms zmu

FLAGS=-g -I/usr/local/include
FLAGS=-O3 -I/usr/local/include
XLIBS= -L. -lmpatrol -lbfd -liberty
XLIBS=

zmdbg.o: zmdbg.c zmdbg.h
	gcc -c $(FLAGS) $<

jmemdst.o: jmemdst.c
	gcc -c $(FLAGS) $<

zm.o: zm.cpp zm.h zmcfg.h zmdbg.h
	g++ -c $(FLAGS) $<

zmc.o: zmc.cpp zm.h zmcfg.h zmdbg.h
	g++ -c $(FLAGS) $<

zma.o: zma.cpp zm.h zmcfg.h zmdbg.h
	g++ -c $(FLAGS) $<

zms.o: zms.cpp zm.h zmcfg.h zmdbg.h
	g++ -c $(FLAGS) $<

zmu.o: zmu.cpp zm.h zmcfg.h zmdbg.h
	g++ -c $(FLAGS) $<

zmc: zmc.o zm.o zmdbg.o jmemdst.o
	g++ $(FLAGS) -Wall -o zmc zmc.o zm.o zmdbg.o jmemdst.o -L/usr/lib/mysql -lmysqlclient -lpthread -ljpeg -ldl -lz -Wl,-E $(XLIBS)

zma: zma.o zm.o zmdbg.o jmemdst.o
	g++ $(FLAGS) -Wall -o zma zma.o zm.o zmdbg.o jmemdst.o -L/usr/lib/mysql -lmysqlclient -lpthread -ljpeg -ldl -lz -Wl,-E $(XLIBS)

zms: zms.o zm.o zmdbg.o jmemdst.o
	g++ $(FLAGS) -Wall -o zms zms.o zm.o zmdbg.o jmemdst.o -L/usr/lib/mysql -lmysqlclient -lpthread -ljpeg -ldl -lz -Wl,-E $(XLIBS)

zmu: zmu.o zm.o zmdbg.o jmemdst.o
	g++ $(FLAGS) -Wall -o zmu zmu.o zm.o zmdbg.o jmemdst.o -L/usr/lib/mysql -lmysqlclient -lpthread -ljpeg -ldl -lz -Wl,-E $(XLIBS)

The following has worked [with newest patches] with buildtools and gcc48 from pkg,
and ffmpeg2, LAMP and libjpeg-turbo from pkgin for OmniOS r151012 and r151014

./bootstrap.sh
# omnios default is 32bit, -m64 is unneeded if perl/libs versions match
export CXXFLAGS=-m64
export CFLAGS=-m64
export PERL=/usr/perl5/5.16.1/bin/amd64/perl
export CPPFLAGS="-I/opt/local/include/ffmpeg2 -I/opt/local/include/mysql
-I/opt/local/include"
# need the -R for runtime load library
export LDFLAGS="-L/opt/local/lib/ffmpeg2 -L/opt/local/lib -R/opt/local/lib
-R/opt/local/lib/ffmpeg2"
export ZM_SSL_LIB=openssl
# need -lsocket -lnsl for recv/send to work, and latest version needs -lsendfile
./configure --with-webdir=/opt/local/share/httpd/htdocs/zm --with-extralibs="-lsocket -lnsl -lsendfile" --with-cgidir=/opt/local/libexec/cgi-bin --with-webuser=www --with-webgroup=www --with-webhost=MACHINENAME.local


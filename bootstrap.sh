#!/bin/bash
<<<<<<< HEAD
libtoolize
aclocal
=======
aclocal -I m4
>>>>>>> master
autoheader
automake --add-missing
autoconf

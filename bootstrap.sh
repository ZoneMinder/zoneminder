#!/bin/bash
libtoolize
aclocal -I m4
autoheader
automake --add-missing
autoconf

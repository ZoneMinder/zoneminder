#!/bin/bash
libtoolize
aclocal
autoheader
automake --add-missing
autoconf

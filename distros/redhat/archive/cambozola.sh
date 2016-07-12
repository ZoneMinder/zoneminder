#!/bin/bash

tar -xvzf cambozola-0.931.tar.gz
mkdir -v cambozola-doc
cd cambozola-0.931
mv -v application.properties build.xml dist.sh *html LICENSE testPages/* ../cambozola-doc
rmdir -v testPages

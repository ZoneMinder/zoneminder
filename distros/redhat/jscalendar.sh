#!/bin/bash

unzip -o jscalendar-1.0.zip
mkdir -v jscalendar-doc
cd jscalendar-1.0
mv -v *html *php doc/* README ../jscalendar-doc
rmdir -v doc

mkdir -p static
mkdir -p static/documentation
mkdir -p static/behaviour-option
mkdir -p static/configurations
mkdir -p static/contact
mkdir -p static/download
mkdir -p static/events-callbacks
mkdir -p static/examples
mkdir -p static/more
mkdir -p static/number-formatting
mkdir -p static/pips
mkdir -p static/reference
mkdir -p static/slider-options
mkdir -p static/slider-read-write
mkdir -p static/slider-values

cp -r documentation/assets static/documentation
cp -r dist static
cp -r tests static

php documentation/_run/router.php "index" > static/index.html
php documentation/_run/router.php "behaviour-option" > static/behaviour-option/index.html
php documentation/_run/router.php "configurations" > static/configurations/index.html
php documentation/_run/router.php "contact" > static/contact/index.html
php documentation/_run/router.php "download" > static/download/index.html
php documentation/_run/router.php "events-callbacks" > static/events-callbacks/index.html
php documentation/_run/router.php "examples" > static/examples/index.html
php documentation/_run/router.php "more" > static/more/index.html
php documentation/_run/router.php "number-formatting" > static/number-formatting/index.html
php documentation/_run/router.php "pips" > static/pips/index.html
php documentation/_run/router.php "reference" > static/reference/index.html
php documentation/_run/router.php "slider-options" > static/slider-options/index.html
php documentation/_run/router.php "slider-read-write" > static/slider-read-write/index.html
php documentation/_run/router.php "slider-values" > static/slider-values/index.html

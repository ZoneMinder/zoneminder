
                            [[  csrf-magic  ]]

Add the following line to the top of all web-accessible PHP pages. If you have
a common file included by everything, put it there.

    include_once '/path/to/csrf-magic.php';

Do it, test it, then forget about it. csrf-magic is protecting you if nothing
bad happens. Read on if you run into problems.


                             TABLE OF CONTENTS
                          + ------------------- +
                            1. TIPS AND TRICKS
                            2. AJAX
                            3. CONFIGURE
                            4. THANKS
                            5. FOOTNOTES
                          + ------------------- +


1.  TIPS AND TRICKS

    * If your JavaScript and AJAX is persistently getting errors, check the
      AJAX section below on how to fix.

    * The CSS overlay protection makes it impossible to display your website
      in frame/iframe elements.  You can disable it with
      csrf_conf('frame-breaker', false) in your csrf_startup() function.

    * csrf-magic will start a session.  To disable, use csrf_conf('auto-session',
      false) in your csrf_startup() function.

    * The default error message is a little user unfriendly.  Write your own
      function which outputs an error message and set csrf_conf('callback',
      'myCallbackFunction') in your csrf_startup() function.

    * Make sure csrf_conf('secret', 'ABCDEFG') has something random in it.  If
      the directory csrf-magic.php is in is writable, csrf-magic will generate
      a secret key for you in the csrf-secret.php file.

    * Remember you can use auto_prepend to include csrf-magic.php on all your
      pages.  You may want to create a stub file which you can include that
      includes csrf-magic.php as well as performs configuration.

    * The default expiration time for tokens is two hours. If you expect your
      users to need longer to fill out forms, be sure to enable double
      submission when the token is invalid.


2.  AJAX

csrf-magic has the ability to dynamically rewrite AJAX requests which use
XMLHttpRequest.  However, due to the invasiveness of this procedure, it is
not enabled by default.  You can enable it by adding this code before you
include csrf-magic.php.

    function csrf_startup() {
        csrf_conf('rewrite-js', '/web/path/to/csrf-magic.js');
    }
    // include_once '/path/to/csrf-magic.php';

(Be sure to place csrf-magic.js somewhere web accessible).

The default method CSRF Magic uses to rewrite AJAX requests will
only work for browsers with support for XmlHttpRequest.prototype (this excludes
all versions of Internet Explorer).  See this page for more information:
http://stackoverflow.com/questions/664315/internet-explorer-8-prototypes-and-xmlhttprequest

However, csrf-magic.js will
automatically detect and play nice with the following JavaScript frameworks:

    * jQuery
    * Prototype
    * MooTools
    * Ext
    * Dojo

(Note 2013-07-16: It has been a long time since this manual support has
been updated, and some JavaScript libraries have placed their copies of XHR
in local variables in closures, which makes it difficult for us to monkey-patch
it in automatically.)

To rewrite your own JavaScript library to use csrf-magic.js, you should modify
your function that generates XMLHttpRequest to have this at the end:

    return new CsrfMagic(xhrObject);

With whatever xhrObject may be. If you have literal instances of XMLHttpRequest
in your code, find and replace ''new XMLHttpRequest'' with ''new CsrfMagic''
(CsrfMagic will automatically instantiate an XMLHttpRequest object in a
cross-platform manner as necessary).

If you don't want csrf-magic monkeying around with your XMLHttpRequest object,
you can manually rewrite your AJAX code to include the variable. The important
information is stored in the global variables csrfMagicName and csrfMagicToken.
CsrfMagic.process may also be of interest, as it takes one parameter, a
querystring, and prepends the CSRF token to the value.


3.  CONFIGURE

csrf-magic has some configuration options that you can set inside the
csrf_startup() function. They are described in csrf-magic.php, and you can
set them using the convenience function csrf_conf($name, $value).

For example, this is a recommended configuration:

    /**
     * This is a function that gets called if a csrf check fails. csrf-magic will
     * then exit afterwards.
     */
    function my_csrf_callback() {
        echo "You're doing bad things young man!";
    }

    function csrf_startup() {

        // While csrf-magic has a handy little heuristic for determining whether
        // or not the content in the buffer is HTML or not, you should really
        // give it a nudge and turn rewriting *off* when the content is
        // not HTML. Implementation details will vary.
        if (isset($_POST['ajax'])) csrf_conf('rewrite', false);

        // This is a secret value that must be set in order to enable username
        // and IP based checks. Don't show this to anyone. A secret id will
        // automatically be generated for you if the directory csrf-magic.php
        // is placed in is writable.
        csrf_conf('secret', 'ABCDEFG123456');

        // This enables JavaScript rewriting and will ensure your AJAX calls
        // don't stop working.
        csrf_conf('rewrite-js', '/csrf-magic.js');

        // This makes csrf-magic call my_csrf_callback() before exiting when
        // there is a bad csrf token. This lets me customize the error page.
        csrf_conf('callback', 'my_csrf_callback');

        // While this is enabled by default to boost backwards compatibility,
        // for security purposes it should ideally be off. Some users can be
        // NATted or have dialup addresses which rotate frequently. Cookies
        // are much more reliable.
        csrf_conf('allow-ip', false);

    }

    // Finally, include the library
    include_once '/path/to/csrf-magic.php';

Configuration gets stored in the $GLOBALS['csrf'] array.


4.  THANKS

My thanks to Chris Shiflett, for unintentionally inspiring the idea, as well
as telling me the original variant of the Bob and Mallory story,
and the Django CSRF Middleware authors, who thought up of this before me.
Gareth Heyes suggested using the frame-breaker option to protect against
CSS overlay attacks.

<?php
// $thisUrl is the base URL used to access ZoneMinder.
//
// If the user attempts to access a privileged view but is not logged in, then he may
// be given the opportunity to log in via the login view.  In that case, the login view
// will save the GET request via the postLoginQuery variable.  After logging in, this
// view receives the postLoginQuery via the login form submission, and we can then
// redirect the user to his original intended destination by appending it to the URL.
?>

(
	function () {
		// Append '?(GET query)' to URL if the GET query is not empty.
		var querySuffix = '<?php
			if (!empty($_SESSION['postLoginQuery'])) {
        parse_str($_SESSION['postLoginQuery'], $queryParams);
        echo '?' . http_build_query($queryParams);
        zm_session_start();
        unset($_SESSION['postLoginQuery']);
        session_write_close();
      }
			?>';

    if ( querySuffix == '?view=login' || querySuffix == '' ) {
      // If we didn't redirect elsewhere, then don't show login page, go to console
      querySuffix = '?view=console';
    }
    var newUrl = querySuffix;
console.log("Current location: " + window.location);
console.log("Redirecting to (" + newUrl + ') from :' + thisUrl);
		window.location.replace(newUrl);
	}
).delay( 500 );

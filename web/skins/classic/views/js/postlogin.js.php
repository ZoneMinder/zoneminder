<?php
//
// If the user attempts to access a privileged view but is not logged in, then he may
// be given the opportunity to log in via the login view.  In that case, the login view
// will save the GET request via the postLoginQuery variable.  After logging in, this
// view receives the postLoginQuery via the login form submission, and we can then
// redirect the user to his original intended destination by appending it to the URL.
//

$redirectSuffix = $user['HomeView'] != '' ? $user['HomeView'] : '?view=console';
if ( !empty($_SESSION['postLoginQuery']) ) {
  parse_str($_SESSION['postLoginQuery'], $queryParams);
  $redirectSuffix = '?' . http_build_query($queryParams);
  zm_session_start();
  unset($_SESSION['postLoginQuery']);
  session_write_close();
}

// If we didn't redirect elsewhere, then don't show login page, go to console
if ( $redirectSuffix == '?view=login' ) $redirectSuffix = '?view=console';
?>

var redirectSuffix = '<?php echo $redirectSuffix ?>';

<?php
//
// If the user attempts to access a privileged view but is not logged in, then he may
// be given the opportunity to log in via the login view.  In that case, the login view
// will save the GET request via the postLoginQuery variable.  After logging in, this
// view receives the postLoginQuery via the login form submission, and we can then
// redirect the user to his original intended destination by appending it to the URL.
//

if (!empty($_REQUEST['postLoginQuery'])) {
  parse_str($_REQUEST['postLoginQuery'], $queryParams);
  $redirectSuffix = '?' . http_build_query($queryParams);
} else if ($user['HomeView'] != '') {
  $redirectSuffix = $user['HomeView'];
} else {
  $redirectSuffix = '?view=console';
}

// If we didn't redirect elsewhere, then don't show login page, go to console
if ( $redirectSuffix == '?view=login' or $redirectSuffix == '?view=logout') {
  Warning('Setting redirect to login causes looping! Redirect to console');
  $redirectSuffix = '?view=console';
}
?>
const redirectSuffix = '<?php echo $redirectSuffix ?>';

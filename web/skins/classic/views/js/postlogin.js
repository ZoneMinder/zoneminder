// Redirect to the requested ZoneMinder url after the user logs in
function postLoginRedirect() {
  console.log('Current location: ' + window.location);
  console.log('Redirecting to (' + redirectSuffix + ') from :' + thisUrl);
  window.location.replace(redirectSuffix);
}

function initPage() {
  setTimeout(postLoginRedirect, 500);
}

// Kick everything off
$j(document).ready(initPage);

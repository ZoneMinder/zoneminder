'use strict';

// Authentication helpers shared by skin.js, console.js and MonitorStream.js for
// handling auth-hash expiry. Loaded as a plain browser script (before skin.js)
// so the functions below are globals; the pure decision helpers are also
// CommonJS-exported for node unit tests (tests/js/auth-helpers.test.js).
//
// The DOM-driven functions reference page globals defined elsewhere (thisUrl,
// currentView, auth_relay, $j, setNavBar, reloadWindow). Those only need to
// exist when the functions are *called* (on a user/visibility event), by which
// point skin.js and the view script have loaded, so the forward references are
// safe. They are never invoked at module load, so requiring this file under
// node (which lacks those globals) stays harmless.

// Decide what to do when an auth-bearing request fails.
//   'login'  - the session/hash is dead, must re-authenticate
//   'retry'  - transient (network/timeout/server) error, worth one silent retry
//   'ignore' - not auth related, leave it to the caller
function authFailureAction(httpStatus) {
  if (httpStatus === 401 || httpStatus === 403) return 'login';
  if (httpStatus === 0 || httpStatus === 408 || httpStatus >= 500) return 'retry';
  return 'ignore';
}

// Build the login URL, preserving the current view so the user lands back where
// they were after re-authenticating.
function loginRedirectUrl(baseUrl, view) {
  return baseUrl + '?view=login&postLoginQuery=' + encodeURIComponent('view=' + (view || 'console'));
}

// Navigate to the login page. Guarded so repeated auth failures (e.g. every
// stream on a console) only trigger one navigation.
let authGoingToLogin = false;
function goToLogin() {
  if (authGoingToLogin) return;
  authGoingToLogin = true;
  window.location.assign(loginRedirectUrl(thisUrl, currentView));
}

// Perform a single silent auth probe against the lightweight navBar status
// endpoint. On success the global auth_hash/auth_relay are refreshed (via
// setNavBar) and onValid() is invoked so the view can repaint its streams with
// the fresh hash. A dead session (401) goes straight to login; transient errors
// are swallowed so we don't bounce the user on a blip.
let authRevalidating = false;
function revalidateAuth(onValid) {
  if (authRevalidating) return;
  authRevalidating = true;
  $j.getJSON(thisUrl + '?view=request&request=status&entity=navBar' + (auth_relay ? '&' + auth_relay : ''))
      .done(function(data) {
        setNavBar(data);
        if (typeof onValid === 'function') onValid();
      })
      .fail(function(jqxhr) {
        if (authFailureAction(jqxhr.status) == 'login') goToLogin();
      })
      .always(function() {
        authRevalidating = false;
      });
}

// When the tab becomes visible again after being hidden/slept, the baked-in auth
// hash on stream <img> elements may have expired. Re-validate auth FIRST so we
// either repaint with a fresh hash or redirect to login, instead of letting
// every stream fire a stale request that 403s. Wired up by skin.js for the
// authenticated views only.
function onAuthVisible() {
  if (document.visibilityState !== 'visible') return;
  revalidateAuth(function() {
    // console repaints its thumbnails via the bootstrap-table reload; other
    // views re-point their streams off the refreshed global auth_hash.
    if (typeof reloadWindow === 'function') reloadWindow();
  });
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {authFailureAction, loginRedirectUrl};
}

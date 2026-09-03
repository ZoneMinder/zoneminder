'use strict';

// Authentication helpers shared by skin.js, console.js and MonitorStream.js.
// Loaded as a plain browser script before skin.js.php and skin.js
// (web/skins/classic/includes/functions.php), so everything below is a global by
// the time any view script runs. The pure helpers and the ZMAuth type are also
// CommonJS-exported for node unit tests (tests/js/auth-helpers.test.js).
//
// The DOM-driven functions reference page globals defined elsewhere (thisUrl,
// currentView, zmAuth, $j, setNavBar, reloadWindow). Those only need to exist
// when the functions are *called* (on a user/visibility event), by which point
// skin.js and the view script have loaded, so the forward references are safe.
// They are never invoked at module load, so requiring this file under node
// (which lacks those globals) stays harmless.

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

// Join a query fragment onto a url with the right separator. A blank fragment
// leaves the url alone, so callers no longer need the `x ? '&'+x : ''` dance
// that produced dangling '?' when authentication is off.
function appendQuery(url, query) {
  const out = url || '';
  if (!query) return out;
  return out + (out.indexOf('?') === -1 ? '?' : '&') + query;
}

// Replace a query parameter in place, or append it when absent. Works on whole
// urls and on bare query fragments such as auth_relay, hence the start anchor
// alongside [?&]; that anchor is also what keeps `auth=` from matching a
// parameter which merely ends in it.
function setUrlParam(url, name, value) {
  const out = url || '';
  const present = new RegExp('(^|[?&])' + name + '=[^&]*', 'i');
  if (present.test(out)) return out.replace(present, '$1' + name + '=' + value);
  return appendQuery(out, name + '=' + value);
}

// Pull the auth hash out of an auth_relay fragment. get_auth_relay() emits
// 'auth=<hash>&user=<name>' under ZM_AUTH_RELAY hashed, but
// 'username=...&password=...' under plain and 'username=...' under none,
// neither of which carries a hash. Returns '' when there is none.
function authHashFromRelay(relay) {
  if (!relay) return '';
  const match = String(relay).match(/(?:^|[?&])auth=(\w+)/i);
  return match ? match[1] : '';
}

// Rewrite a stream <img> URL with a fresh auth hash and (optionally) a fresh
// connkey, used when reconnecting a broken zms stream. The old auth hash may
// have expired past AUTH_HASH_TTL and the zms process behind the old connkey has
// exited, so a clean reconnect needs both swapped. Pure (no DOM); prefer
// zmAuth.applyTo(), which feeds this the current hash and also handles the
// plain/none relay forms.
function rebuildStreamSrc(src, authHash, connKey) {
  let out = src || '';
  if (authHash) out = setUrlParam(out, 'auth', authHash);
  if (connKey !== undefined && connKey !== null && connKey !== '') {
    out = setUrlParam(out, 'connkey', connKey);
  }
  return out;
}

// The page's authentication state, and the single place it is stored.
//
// There used to be two globals: auth_relay (the query fragment every AJAX call
// is authenticated with) and auth_hash (the bare hash stamped into stream <img>
// URLs). Only auth_relay was refreshed unconditionally by the server -
// ajax/stream.php omitted `auth` from its reply whenever it matched the hash the
// request carried, and since that hash came from auth_relay, a drifted auth_hash
// could never be corrected. Reconnecting streams stamped the drifted global into
// their src and zms 403'd every request for hours afterwards.
//
// So the hash is not stored at all now; it is derived from the relay on demand.
// The two cannot disagree because there is only one value.
class ZMAuth {
  constructor(relay) {
    this.relay = relay || '';
  }

  // The bare auth hash, or '' under the plain/none relay forms.
  get hash() {
    return authHashFromRelay(this.relay);
  }

  // Absorb the authentication fields of any AJAX response. Returns true when
  // the credential actually changed, so callers can repaint streams only when
  // there is something to repaint. Responses carry auth_relay whenever
  // authentication is on; the `auth`-only branch covers partial replies.
  update(data) {
    if (!data) return false;
    if (data.auth_relay) {
      if (data.auth_relay === this.relay) return false;
      this.relay = data.auth_relay;
      return true;
    }
    if (data.auth && data.auth !== this.hash) {
      this.relay = setUrlParam(this.relay, 'auth', data.auth);
      return true;
    }
    return false;
  }

  // Authenticate a URL. Safe to call when authentication is off (no-op).
  appendTo(url) {
    return appendQuery(url, this.relay);
  }

  // Point a stream URL at the current credential, optionally with a fresh
  // connkey. A src that already carries auth= has just that parameter swapped
  // so the rest of its stream options survive; one that carries none gets the
  // whole relay, which is what brings user= (or username=/password=) along.
  applyTo(src, connKey) {
    // No src means there is no stream to point anywhere. Returning '' keeps
    // callers that treat a blank src as "nothing to load" working; building
    // '?auth=...' here would resolve against the current page and load the
    // surrounding HTML as an image.
    if (!src) return '';
    let out = src;
    if (this.relay) {
      if (/(?:^|[?&])auth=\w*/i.test(out)) {
        if (this.hash) out = setUrlParam(out, 'auth', this.hash);
      } else {
        out = this.appendTo(out);
      }
    }
    return rebuildStreamSrc(out, '', connKey);
  }
}

// Navigate to the login page. Guarded so repeated auth failures (e.g. every
// stream on a console) only trigger one navigation.
let authGoingToLogin = false;
function goToLogin() {
  if (authGoingToLogin) return;
  authGoingToLogin = true;
  window.location.assign(loginRedirectUrl(thisUrl, currentView));
}

// A hidden tab has its timers throttled, and a slept/frozen one has them
// stopped outright, so nothing refreshes the credential while we are in the
// background. The server rotates the hash at half of AUTH_HASH_TTL
// (generateAuthHash()), so a hash baked into a stream <img> src or a table url
// an hour or more before we come back is probably already dead: every request
// made off it 403s and fills the log with auth errors. Remember when we went
// hidden so refocus can tell an alt-tab from an overnight sleep.
const AUTH_STALE_MS = 60 * 60 * 1000;
let authHiddenAt = 0;

// Pure so it can be tested without faking the clock or the DOM.
function authHiddenTooLong(hiddenAt, now) {
  return hiddenAt !== 0 && (now - hiddenAt) > AUTH_STALE_MS;
}

// Perform a single silent auth probe against the lightweight navBar status
// endpoint. zmAuth is refreshed (via setNavBar) and the queued callbacks are
// then invoked so each view can repaint its streams with the fresh credential.
// A dead session (401) goes straight to login and the callbacks are dropped;
// other failures still run them, since a transient blip is no reason to leave
// the page's streams stopped. Concurrent callers share the one request.
let authRevalidating = false;
const authPendingCallbacks = [];
function revalidateAuth(onValid) {
  if (typeof onValid === 'function') authPendingCallbacks.push(onValid);
  if (authRevalidating) return;
  authRevalidating = true;
  $j.getJSON(zmAuth.appendTo(thisUrl + '?view=request&request=status&entity=navBar'))
      .done(function(data) {
        setNavBar(data);
        // Only a reply we actually got clears the staleness; until then every
        // whenAuthFresh() caller keeps queueing instead of running early.
        authHiddenAt = 0;
      })
      .fail(function(jqxhr) {
        if (authFailureAction(jqxhr.status) == 'login') goToLogin();
      })
      .always(function() {
        authRevalidating = false;
        const callbacks = authPendingCallbacks.splice(0, authPendingCallbacks.length);
        if (authGoingToLogin) return;
        for (let i = 0; i < callbacks.length; i++) callbacks[i]();
      });
}

// Run cb against a credential we have reason to trust. After a short hide the
// hash is still good and cb runs straight away; after a long one cb is queued
// behind the revalidation, so nothing restarts a stream or a table poll on the
// expired hash. Use this anywhere a visibility/resume handler kicks off
// authenticated requests.
function whenAuthFresh(cb) {
  if (!authHiddenTooLong(authHiddenAt, Date.now())) {
    cb();
    return;
  }
  revalidateAuth(cb);
}

// When the tab becomes visible again after being hidden/slept, the baked-in auth
// hash on stream <img> elements may have expired. Re-validate auth FIRST so we
// either repaint with a fresh hash or redirect to login, instead of letting
// every stream fire a stale request that 403s. Wired up by skin.js for the
// authenticated views only.
function onAuthVisible() {
  if (document.visibilityState !== 'visible') {
    if (!authHiddenAt) authHiddenAt = Date.now();
    return;
  }
  revalidateAuth(function() {
    // console repaints its thumbnails via the bootstrap-table reload; other
    // views re-point their streams off the refreshed credential.
    if (typeof reloadWindow === 'function') reloadWindow();
  });
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    authFailureAction,
    loginRedirectUrl,
    appendQuery,
    setUrlParam,
    authHashFromRelay,
    rebuildStreamSrc,
    authHiddenTooLong,
    AUTH_STALE_MS,
    ZMAuth,
  };
}

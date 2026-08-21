<?php
//
// ZoneMinder network address helpers
//
// Kept dependency-free so it can be included from anywhere - session.php pulls
// it in before the rest of the web bootstrap exists - and unit tested
// standalone. Policy decisions that happen to involve addresses do not belong
// here; the auth hash's use of getRemoteAddr() lives in auth.php.
//

// Return the effective client address: the first hop of X-Forwarded-For when
// present (reverse-proxy setups), otherwise REMOTE_ADDR. Only the first value is
// used to avoid trusting spoofed multi-value headers.
//
// Note that X-Forwarded-For is taken on trust; there is no trusted-proxy list.
// A client connecting directly can therefore choose the address it is bound to.
// That is long-standing behaviour, kept here so generation and validation agree,
// but it means ZM_AUTH_HASH_IPS is only meaningful when a proxy you control
// overwrites the header, or when nothing is proxied at all.
function getRemoteAddr() {
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
  }
  return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}
?>

<?php
//
// ZoneMinder web log redaction helpers
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//
// Credentials never go into a log line. Log files are often world-readable,
// debug logs get pasted into support threads, and the Logs table is readable
// by every System=View user. These helpers turn credentials into something
// that can be logged. No dependencies, so they can be loaded and tested alone.

namespace ZM;

// A short, non-reversible tag for a credential such as an auth hash, a token
// or a session id: "fp:" plus the first 8 hex digits of its SHA-256. Lets log
// lines be matched up (zms and zm_rtsp_server log the same tag through
// zm::crypto::LogFingerprint) without the credential being recoverable. Not
// for passwords: a password's fingerprint can be brute-forced offline.
function logFingerprint($value) {
  if ($value === null or $value === '') return 'fp:none';
  return 'fp:'.substr(hash('sha256', (string)$value), 0, 8);
}

// A URL as it may be logged: no user:password@ and no query string, which may
// carry an auth hash, a token or a password. "?..." marks a removed query.
function urlForLog($url) {
  $url = (string)$url;
  $query_at = strpos($url, '?');
  $suffix = '';
  if ($query_at !== false) {
    $url = substr($url, 0, $query_at);
    $suffix = '?...';
  }
  return preg_replace('#^([a-z][a-z0-9+.-]*://)[^/@]*@#i', '$1', $url).$suffix;
}

// A copy of $data (such as $_REQUEST or $_SESSION) fit for print_r() into a
// log: passwords and secrets replaced, tokens, auth hashes, session ids and
// CSRF tokens replaced by their fingerprint, credentials removed from URLs.
function redactForLog($data, $key = null) {
  if (is_array($data)) {
    $out = array();
    foreach ($data as $k => $v) $out[$k] = redactForLog($v, $k);
    return $out;
  }
  if (is_object($data)) return '['.get_class($data).']';
  if ($key !== null) {
    $k = (string)$key;
    if (preg_match('/pass|pwd|secret/i', $k)) return '[redacted]';
    if (preg_match('/^(auth|token|access_token|refresh_token|jwt|ZMSESSID|__csrf_magic)$|^AuthHash(?!GeneratedAt)/i', $k)) {
      return logFingerprint($data);
    }
  }
  if (is_string($data) and preg_match('#^[a-z][a-z0-9+.-]*://[^/@\s]*@#i', $data)) {
    return preg_replace('#^([a-z][a-z0-9+.-]*://)[^/@\s]*@#i', '$1', $data);
  }
  return $data;
}

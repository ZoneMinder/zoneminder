<?php
//
// ZoneMinder HTTP Range request parsing
// Copyright (C) 2026 ZoneMinder LLC
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
//
// Deliberately free of dependencies: nothing here needs the config, the
// database or the session, so it can be reasoned about and tested on its own.
//

// Work out which bytes a Range request asks for, given a representation of
// $size bytes.
//
// Returns one of three things, which the caller has to tell apart with ===:
//
//   false        no range to honour. Send the whole representation, 200.
//   null         a range was asked for and cannot be satisfied. Send 416 with
//                "Content-Range: bytes */$size" so the client learns the real
//                length instead of waiting for bytes that will never come.
//   array(b, e)  the inclusive range to send, 206.
//
// The caller must take the length from the returned range rather than from
// the request: a range may legitimately run past the end of the file, and it
// is satisfied by whatever is there. Answering with the length that was asked
// for promises more than the body can deliver, and a player that reads the
// short body treats it as a broken file.
function parseHttpRange($header, $size) {
  if (!is_string($header) or ($header === '')) return false;
  $size = intval($size);

  // "bytes" is the only range unit HTTP defines. Anything else is not ours to
  // interpret, and a server may always ignore a range it does not understand.
  if (!preg_match('/^\s*bytes\s*=\s*(.*)$/is', $header, $matches)) return false;
  $spec = trim($matches[1]);
  if ($spec === '') return false;

  // A request for several ranges should strictly be answered with a
  // multipart/byteranges body. Serve the first one instead: Content-Range
  // names exactly which bytes these are, so the client can ask for the rest,
  // and falling back to the whole representation is not an option when these
  // are event videos of hundreds of megabytes. Players, and the byte-range
  // HLS manifest VideoStore writes, only ever ask for one range anyway.
  $parts = explode(',', $spec);
  $first = trim($parts[0]);

  if (!preg_match('/^(\d*)\s*-\s*(\d*)$/', $first, $range)) return false;
  $from = $range[1];
  $to = $range[2];
  // "bytes=-" names neither end and is malformed rather than unsatisfiable.
  if (($from === '') and ($to === '')) return false;

  if ($from === '') {
    // "-N" is the LAST n bytes, not a range starting at zero. Reading it as
    // the latter would quietly serve the wrong part of the file.
    $suffix = intval($to);
    // A suffix of zero asks for no bytes at all, which cannot be satisfied.
    if (($suffix <= 0) or ($size <= 0)) return null;
    $begin = ($suffix >= $size) ? 0 : $size - $suffix;
    $end = $size - 1;
  } else {
    $begin = intval($from);
    $end = ($to === '') ? ($size - 1) : intval($to);
    // Clamp rather than reject: a client is entitled to ask for more than is
    // there, and gets what is there.
    if ($end > ($size - 1)) $end = $size - 1;
  }

  if (($size <= 0) or ($begin < 0) or ($begin >= $size) or ($begin > $end)) return null;

  return array($begin, $end);
}

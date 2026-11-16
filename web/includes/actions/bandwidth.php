<?php
//
// ZoneMinder web action file
// Copyright (C) 2019 ZoneMinder LLC
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


if ( $action == 'bandwidth' && isset($_REQUEST['newBandwidth']) ) {
  $newBandwidth = validStr($_REQUEST['newBandwidth']);
  // Storing an unrecognised profile would leave the skin unable to define its
  // ZM_WEB_* constants on every subsequent request, so reject it here instead
  // of persisting it to the cookie.
  if ( !isValidBandwidth($newBandwidth) ) {
    ZM\Error('Ignoring invalid bandwidth value: '.$newBandwidth);
  } else {
    $_COOKIE['zmBandwidth'] = $newBandwidth;
    zm_setcookie('zmBandwidth', $newBandwidth);
    $refreshParent = true;
    $view = 'none';
    $closePopup = true;
  }
}
?>

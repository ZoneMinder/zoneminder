<?php
//
// ZoneMinder web bandwidth view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

$newBandwidth = $_COOKIE['zmBandwidth'];

# Limit available options to what are available in user
if ( $user && !empty($user->MaxBandwidth()) ) {
  if ( $user->MaxBandwidth() == 'low' ) {
    unset($bandwidth_options['high']);
    unset($bandwidth_options['medium']);
  } else if ( $user->MaxBandwidth() == 'medium' ) {
    unset($bandwidth_options['high']);
  }
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Bandwidth'));
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Bandwidth') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="view" value="bandwidth"/>
        <input type="hidden" name="action" value="bandwidth"/>
        <p><?php echo translate('SetNewBandwidth') ?></p>
        <p><?php echo buildSelect('newBandwidth', $bandwidth_options) ?></p>
        <div id="contentButtons">
          <button type="submit" value="Save"><?php echo translate('Save') ?></button>
          <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

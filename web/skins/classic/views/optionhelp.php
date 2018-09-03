<?php
//
// ZoneMinder web option help view file, $Date$, $Revision$
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

$optionHelpIndex = preg_replace( '/^ZM_/', '', $_REQUEST['option'] );
$optionHelpText = !empty($OLANG[$optionHelpIndex])?$OLANG[$optionHelpIndex]['Help']:$config[$_REQUEST['option']]['Help'];
$optionHelpText = validHtmlStr($optionHelpText);
$optionHelpText = preg_replace( "/~~/", "<br/>", $optionHelpText );
$optionHelpText = preg_replace( "/\[(.+)\]\((.+)\)/", "<a href=\"$2\" target=\"_blank\">$1</a>", $optionHelpText );

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('OptionHelp') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('OptionHelp') ?></h2>
    </div>
    <div id="content">
      <h3><?php echo validHtmlStr($_REQUEST['option']) ?></h3>
      <p class="textblock"><?php echo $optionHelpText ?></p>
    </div>
  </div>
</body>
</html>

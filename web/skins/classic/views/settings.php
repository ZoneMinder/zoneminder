<?php
//
// ZoneMinder web settings view file, $Date$, $Revision$
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

if ( !canView( 'Control' ) )
{
    $view = "error";
    return;
}
$monitor = dbFetchMonitor( $_REQUEST['mid'] );

$zmuCommand = getZmuCommand( " -m ".escapeshellarg($_REQUEST['mid'])." -B -C -H -O" );
$zmuOutput = exec( $zmuCommand );
list( $brightness, $contrast, $hue, $colour ) = explode( ' ', $zmuOutput );

$monitor['Brightness'] = $brightness;
$monitor['Contrast'] = $contrast;
$monitor['Hue'] = $hue;
$monitor['Colour'] = $colour;

$focusWindow = true;

xhtmlHeaders(__FILE__, validHtmlStr($monitor['Name'])." - ".translate('Settings') );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo validHtmlStr($monitor['Name']) ?> - <?php echo translate('Settings') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="settings"/>
        <input type="hidden" name="mid" value="<?php echo validInt($_REQUEST['mid']) ?>"/>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Brightness') ?></th>
              <td><input type="text" name="newBrightness" value="<?php echo $monitor['Brightness'] ?>" size="8"<?php if ( !canView( 'Control' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Contrast') ?></th>
              <td><input type="text" name="newContrast" value="<?php echo $monitor['Contrast'] ?>" size="8"<?php if ( !canView( 'Control' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Hue') ?></th>
              <td><input type="text" name="newHue" value="<?php echo $monitor['Hue'] ?>" size="8"<?php if ( !canView( 'Control' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Colour') ?></th>
              <td><input type="text" name="newColour" value="<?php echo $monitor['Colour'] ?>" size="8"<?php if ( !canView( 'Control' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Save') ?>"<?php if ( !canView( 'Control' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?php echo translate('Close') ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

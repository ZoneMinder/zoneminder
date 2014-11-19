<?php
//
// ZoneMinder web controls file, $Date$, $Revision$
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canView( 'Control' ) )
{
    $view = "error";
    return;
}

$controls = dbFetchAll( 'SELECT * FROM Controls ORDER BY Id' );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['ControlCaps'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow();"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['ControlCaps'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>" onsubmit="return( confirmDelete( 'Warning, deleting a control will reset all monitors that use it to be uncontrollable.\nAre you sure you wish to delete?' ) );">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colName"><?= $SLANG['Name'] ?></th>
              <th class="colType"><?= $SLANG['Type'] ?></th>
              <th class="colProtocol"><?= $SLANG['Protocol'] ?></th>
              <th class="colCanMove"><?= $SLANG['CanMove'] ?></th>
              <th class="colCanZoom"><?= $SLANG['CanZoom'] ?></th>
              <th class="colCanFocus"><?= $SLANG['CanFocus'] ?></th>
              <th class="colCanIris"><?= $SLANG['CanIris'] ?></th>
              <th class="colCanWhiteBal"><?= $SLANG['CanWhiteBal'] ?></th>
              <th class="colHasPresets"><?= $SLANG['HasPresets'] ?></th>
              <th class="colMark"><?= $SLANG['Mark'] ?></th>
            </tr>
          </thead>
          <tbody>
<?php
foreach( $controls as $control )
{
?>
            <tr>
              <td class="colName"><?= makePopupLink( '?view=controlcap&amp;cid='.$control['Id'], 'zmControlCap', 'controlcap', $control['Name'], canView( 'Control' ) ) ?></td>
              <td class="colType"><?= $control['Type'] ?></td>
              <td class="colProtocol"><?= $control['Protocol'] ?></td>
              <td class="colCanMove"><?= $control['CanMove']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanZoom"><?= $control['CanZoom']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanFocus"><?= $control['CanFocus']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanIris"><?= $control['CanIris']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanWhiteBal"><?= $control['CanWhite']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colHasPresets"><?= $control['HasHomePreset']?'H':'' ?><?= $control['HasPresets']?$control['NumPresets']:'0' ?></td>
              <td class="colMark"><input type="checkbox" name="markCids[]" value="<?= $control['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !canEdit( 'Control' ) ) {?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?= $SLANG['AddNewControl'] ?>" onclick="createPopup( '?view=controlcap', 'zmControlCap', 'controlcap' );"<?php if ( !canEdit( 'Control' ) ) {?> disabled="disabled"<?php } ?>/><input type="submit" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" disabled="disabled"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

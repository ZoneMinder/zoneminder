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
        <a href="#" onclick="closeWindow();"><?php echo $SLANG['Close'] ?></a>
      </div>
      <h2><?php echo $SLANG['ControlCaps'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return( confirmDelete( 'Warning, deleting a control will reset all monitors that use it to be uncontrollable.\nAre you sure you wish to delete?' ) );">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colName"><?php echo $SLANG['Name'] ?></th>
              <th class="colType"><?php echo $SLANG['Type'] ?></th>
              <th class="colProtocol"><?php echo $SLANG['Protocol'] ?></th>
              <th class="colCanMove"><?php echo $SLANG['CanMove'] ?></th>
              <th class="colCanZoom"><?php echo $SLANG['CanZoom'] ?></th>
              <th class="colCanFocus"><?php echo $SLANG['CanFocus'] ?></th>
              <th class="colCanIris"><?php echo $SLANG['CanIris'] ?></th>
              <th class="colCanWhiteBal"><?php echo $SLANG['CanWhiteBal'] ?></th>
              <th class="colHasPresets"><?php echo $SLANG['HasPresets'] ?></th>
              <th class="colMark"><?php echo $SLANG['Mark'] ?></th>
            </tr>
          </thead>
          <tbody>
<?php
foreach( $controls as $control )
{
?>
            <tr>
              <td class="colName"><?php echo makePopupLink( '?view=controlcap&amp;cid='.$control['Id'], 'zmControlCap', 'controlcap', $control['Name'], canView( 'Control' ) ) ?></td>
              <td class="colType"><?php echo $control['Type'] ?></td>
              <td class="colProtocol"><?php echo $control['Protocol'] ?></td>
              <td class="colCanMove"><?php echo $control['CanMove']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanZoom"><?php echo $control['CanZoom']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanFocus"><?php echo $control['CanFocus']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanIris"><?php echo $control['CanIris']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colCanWhiteBal"><?php echo $control['CanWhite']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colHasPresets"><?php echo $control['HasHomePreset']?'H':'' ?><?php echo $control['HasPresets']?$control['NumPresets']:'0' ?></td>
              <td class="colMark"><input type="checkbox" name="markCids[]" value="<?php echo $control['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !canEdit( 'Control' ) ) {?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?php echo $SLANG['AddNewControl'] ?>" onclick="createPopup( '?view=controlcap', 'zmControlCap', 'controlcap' );"<?php if ( !canEdit( 'Control' ) ) {?> disabled="disabled"<?php } ?>/><input type="submit" name="deleteBtn" value="<?php echo $SLANG['Delete'] ?>" disabled="disabled"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

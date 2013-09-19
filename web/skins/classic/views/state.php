<?php
//
// ZoneMinder web run state view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
    $view = "error";
    return;
}
$running = daemonCheck();

$states = dbFetchAll( "select * from States" );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['RunState'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['RunState'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
<?php
if ( empty($_REQUEST['apply']) )
{
?>
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="apply" value="1"/>
        <p>
          <select name="runState" onchange="checkState( this );">
<?php
    if ( $running )
    {
?>
            <option value="stop" selected="selected"><?= $SLANG['Stop'] ?></option>
            <option value="restart"><?= $SLANG['Restart'] ?></option>
<?php
    }
    else
    {
?>
            <option value="start" selected="selected"><?= $SLANG['Start'] ?></option>
<?php
    }
?>
<?php
    foreach ( $states as $state )
    {
?>
            <option value="<?= $state['Name'] ?>"><?= $state['Name'] ?></option>
<?php
    }
?>
          </select>
        </p>
        <table id="contentTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?= $SLANG['NewState'] ?></th>
              <td><input type="text" name="newState" value="" size="16" onchange="checkState( this );"/></td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Apply'] ?>"/>
          <input type="button" name="saveBtn" value="<?= $SLANG['Save'] ?>" disabled="disabled" onclick="saveState( this );"/>
          <input type="button" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" disabled="disabled" onclick="deleteState( this );"/>
          <input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
<?php
}
else
{
?>
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="state"/>
        <input type="hidden" name="runState" value="<?= validHtmlStr($_REQUEST['runState']) ?>"/>
        <p><?= $SLANG['ApplyingStateChange'] ?></p>
        <p><?= $SLANG['PleaseWait'] ?></p>
<?php
}
?>
      </form>
    </div>
  </div>
</body>
</html>

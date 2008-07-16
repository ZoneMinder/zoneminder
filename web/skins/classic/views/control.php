<?php
//
// ZoneMinder web control view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
    $_REQUEST['view'] = "error";
    return;
}

$groupSql = "";
if ( !empty($_REQUEST['group']) )
{
    $sql = "select * from Groups where Id = '".$group."'";
    $row = dbFetchOne( $sql );
    $groupSql = " and find_in_set( Id, '".$row['MonitorIds']."' )";
}

$sql = "select * from Monitors where Function != 'None' and Controllable = 1$groupSql order by Sequence";
$mids = array();
foreach( dbFetchAll( $sql ) as $row )
{
    if ( !visibleMonitor( $row['Id'] ) )
    {
        continue;
    }
    if ( empty($mid) )
        $mid = $row['Id'];
    $mids[$row['Id']] = $row['Name'];
}

require_once( ZM_SKIN_PATH.'/includes/control_functions.php' );

$sql = "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = '".$mid."'";
$monitor = dbFetchOne( $sql );

$cmds = getControlCommands( $monitor );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Control'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow();"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['Control'] ?></h2>
      <div id="headerControl">
        <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
          <input type="hidden" name="view" value="<?= $_REQUEST['view'] ?>"/>
          <?= buildSelect( "mid", $mids, "this.form.submit();" ); ?>
        </form>
      </div>
    </div>
    <div id="content">
      <div id="ptzControls">
        <div id="controlsPanel">
<?php
        if ( $monitor['CanFocus'] )
            echo controlFocus( $monitor );
        if ( $monitor['CanZoom'] )
            echo controlZoom( $monitor );
        if ( $monitor['CanMove'] || ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] ) )
        {
?>
          <div id="pantiltPanel">
<?php
            if ( $monitor['CanMove'] )
                echo controlPanTilt( $monitor );
            if ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] )
                echo controlPower( $monitor );
?>
          </div>
<?php
        }
        if ( $monitor['CanIris'] )
            echo controlIris( $monitor );
        if ( $monitor['CanWhite'] )
            echo controlWhite( $monitor );
?>
        </div>
<?php
        if ( $monitor['HasPresets'] )
            echo controlPresets( $monitor );
?>
      </div>
    </div>
  </div>
</body>
</html>

<?php
//
// ZoneMinder web user view file, $Date$, $Revision$
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

$selfEdit = ZM_USER_SELF_EDIT && $_REQUEST['uid'] == $user['Id'];

if ( !canEdit( 'System' ) && !$selfEdit )
{
    $view = "error";
    return;
}
if ( !($newUser = dbFetchOne( 'SELECT * FROM Users WHERE Id = ?', NULL, ARRAY($_REQUEST['uid'])) ) ); {
    $newUser = array();
    $newUser['Username'] = $SLANG['NewUser'];
    $newUser['Enabled'] = 1;
    $newUser['MonitorIds'] = '';
}

$monitorIds = array_flip(explode( ',', $newUser['MonitorIds'] ));

$yesno = array( 0=>$SLANG['No'], 1=>$SLANG['Yes'] );
$nv = array( 'None'=>$SLANG['None'], 'View'=>$SLANG['View'] );
$nve = array( 'None'=>$SLANG['None'], 'View'=>$SLANG['View'], 'Edit'=>$SLANG['Edit'] );
$bandwidths = array_merge( array( ""=>"" ), $bwArray );
$langs = array_merge( array( ""=>"" ), getLanguages() );

$sql = "select Id,Name from Monitors order by Sequence asc";
$monitors = array();
foreach( dbFetchAll( $sql ) as $monitor )
{
    $monitors[] = $monitor;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['User']." - ".$newUser['Username'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo $SLANG['User']." - ".$newUser['Username'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return validateForm( this, <?php echo empty($newUser['Password'])?'true':'false' ?> )">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="user"/>
        <input type="hidden" name="uid" value="<?php echo validHtmlStr($_REQUEST['uid']) ?>"/>
        <input type="hidden" name="newUser[MonitorIds]" value="<?php echo $newUser['MonitorIds'] ?>"/>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
<?php
if ( canEdit( 'System' ) )
{
?>
            <tr>
              <th scope="row"><?php echo $SLANG['Username'] ?></th>
              <td><input type="text" name="newUser[Username]" value="<?php echo $newUser['Username'] ?>"/></td>
            </tr>
<?php
}
?>
            <tr>
              <th scope="row"><?php echo $SLANG['NewPassword'] ?></th>
              <td><input type="password" name="newUser[Password]" value=""/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['ConfirmPassword'] ?></th>
              <td><input type="password" name="conf_password" value=""/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['Language'] ?></th>
              <td><?php echo buildSelect( "newUser[Language]", $langs ) ?></td>
            </tr>
<?php
if ( canEdit( 'System' ) )
{
?>
            <tr>
              <th scope="row"><?php echo $SLANG['Enabled'] ?></th>
              <td><?php echo buildSelect( "newUser[Enabled]", $yesno ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['Stream'] ?></th>
              <td><?php echo buildSelect( "newUser[Stream]", $nv ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['Events'] ?></th>
              <td><?php echo buildSelect( "newUser[Events]", $nve ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['Control'] ?></th>
              <td><?php echo buildSelect( "newUser[Control]", $nve ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['Monitors'] ?></th>
              <td><?php echo buildSelect( "newUser[Monitors]", $nve ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['System'] ?></th>
              <td><?php echo buildSelect( "newUser[System]", $nve ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['MaxBandwidth'] ?></th>
              <td><?php echo buildSelect( "newUser[MaxBandwidth]", $bandwidths ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo $SLANG['RestrictedMonitors'] ?></th>
              <td>
                <select name="monitorIds" size="4" multiple="multiple">
<?php
    foreach ( $monitors as $monitor )
    {
        if ( visibleMonitor( $monitor['Id'] ) )
        {
?>
                  <option value="<?php echo $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?php echo htmlentities($monitor['Name']) ?></option>
<?php
        }
    }
?>
                </select>
              </td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo $SLANG['Save'] ?>"/><input type="button" value="<?php echo $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

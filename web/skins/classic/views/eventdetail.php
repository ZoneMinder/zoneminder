<?php
//
// ZoneMinder web event detail view file, $Date$, $Revision$
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

if ( !canEdit( 'Events' ) )
{
    $_REQUEST['view'] = "error";
    return;
}
if ( isset($_REQUEST['eid']) )
{
    $sql = "select E.* from Events as E where E.Id = '".dbEscape($_REQUEST['eid'])."'";
    $newEvent = dbFetchOne( $sql );
}
elseif ( isset($_REQUEST['eids']) )
{
    $sql = "select E.* from Events as E where ";
    $sqlWhere = array();
    foreach ( $_REQUEST['eids'] as $eid )
    {
        $sqlWhere[] = "E.Id = '".dbEscape($eid)."'";
    }
    $sql .= join( " or ", $sqlWhere );
    foreach( dbFetchAll( $sql ) as $row )
    {
        if ( !isset($newEvent) )
        {
            $newEvent = $row;
        }
        else
        {
            if ( $newEvent['Cause'] && $newEvent['Cause'] != $row['Cause'] )
                $newEvent['Cause'] = "";
            if ( $newEvent['Notes'] && $newEvent['Notes'] != $row['Notes'] )
                $newEvent['Notes'] = "";
        }
    }
}

$focusWindow = true;

if ( isset($_REQUEST['eid']) )
    xhtmlHeaders(__FILE__, $SLANG['Event']." - ".$_REQUEST['eid'] );
else
    xhtmlHeaders(__FILE__, $SLANG['Events'] );
?>
<body>
  <div id="page">
    <div id="header">
<?php
if ( isset($_REQUEST['eid']) )
{
?>
      <h2><?= $SLANG['Event'] ?> <?= $_REQUEST['eid'] ?></h2>
<?php
}
else
{
?>
      <h2><?= $SLANG['Events'] ?></h2>
<?php
}
?>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
<?php
if ( isset($_REQUEST['eid']) )
{
?>
        <input type="hidden" name="view" value="<?= $_REQUEST['view'] ?>"/>
        <input type="hidden" name="action" value="eventdetail"/>
        <input type="hidden" name="eid" value="<?= $_REQUEST['eid'] ?>"/>
<?php
}
elseif ( isset($_REQUEST['eids']) )
{
?>
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="eventdetail"/>
<?php
    foreach ( $_REQUEST['eids'] as $eid )
    {
?>
        <input type="hidden" name="markEids[]" value="<?= $eid ?>"/>
<?php
    }
}
?>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?= $SLANG['Cause'] ?></td>
              <td><input type="text" name="newEvent[Cause]" value="<?= $newEvent['Cause'] ?>" size="32"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['Notes'] ?></td>
              <td><textarea name="newEvent[Notes]" rows="6" cols="50"><?= $newEvent['Notes'] ?></textarea></td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

<?php
//
// ZoneMinder web event detail view file, $Date$, $Revision$
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

if ( !canEdit('Events') ) {
  $view = 'error';
  return;
}
if ( isset($_REQUEST['eid']) ) {
  $mode = 'single';
  $eid = validInt($_REQUEST['eid']);
  $newEvent = dbFetchOne('SELECT E.* FROM Events AS E WHERE E.Id = ?', NULL, array($eid));
} elseif ( isset($_REQUEST['eids']) ) {
  $mode = 'multi';
  $sql = 'SELECT E.* FROM Events AS E WHERE ';
  $sqlWhere = array();
  $sqlValues = array();
  foreach ( $_REQUEST['eids'] as $eid ) {
    $sqlWhere[] = 'E.Id = ?';
    $sqlValues[] = validInt($eid);
  }
  unset($eid);
  $sql .= join(' OR ', $sqlWhere);
  foreach( dbFetchAll( $sql, NULL, $sqlValues ) as $row ) {
    if ( !isset($newEvent) ) {
      $newEvent = $row;
    } else {
      if ( $newEvent['Cause'] && $newEvent['Cause'] != $row['Cause'] )
        $newEvent['Cause'] = '';
      if ( $newEvent['Notes'] && $newEvent['Notes'] != $row['Notes'] )
        $newEvent['Notes'] = '';
    }
  }
} else {
  $mode = '';
}

$focusWindow = true;

if ( $mode == 'single' )
    xhtmlHeaders(__FILE__, translate('Event').' - '.$eid );
else
    xhtmlHeaders(__FILE__, translate('Events') );
?>
<body>
  <div id="page">
    <div id="header">
<?php
if ( $mode == 'single' ) {
?>
      <h2><?php echo translate('Event') ?> <?php echo $eid ?></h2>
<?php
} else {
?>
      <h2><?php echo translate('Events') ?></h2>
<?php
}
?>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="action" value="eventdetail"/>
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
<?php
if ( $mode == 'single' ) {
?>
        <input type="hidden" name="markEids[]" value="<?php echo validInt($eid) ?>"/>
<?php
} else if ( $mode = 'multi' ) {
?>
<?php
    foreach ( $_REQUEST['eids'] as $eid ) {
?>
        <input type="hidden" name="markEids[]" value="<?php echo validInt($eid) ?>"/>
<?php
    }
}
?>
        <table id="contentTable" class="major">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Cause') ?></th>
              <td><input type="text" name="newEvent[Cause]" value="<?php echo validHtmlStr($newEvent['Cause']) ?>" size="32"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Notes') ?></th>
              <td><textarea name="newEvent[Notes]" rows="6" cols="50"><?php echo validHtmlStr($newEvent['Notes']) ?></textarea></td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <button type="submit" value="Save" <?php echo !canEdit('Events') ? ' disabled="disabled"' : '' ?>>
          <?php echo translate('Save') ?>
          </button>
          <button type="button" data-on-click="closeWindow"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

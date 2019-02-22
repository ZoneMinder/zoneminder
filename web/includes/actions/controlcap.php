<?php
//
// ZoneMinder web action file
// Copyright (C) 2019 ZoneMinder LLC
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


if ( !canEdit('Control') ) {
  ZM\Warning('Need Control permissions to edit control capabilities');
  return;
} // end if !canEdit Controls

if ( $action == 'controlcap' ) {
  require_once('includes/Control.php');
  $Control = new ZM\Control( !empty($_REQUEST['cid']) ? $_REQUEST['cid'] : null );

  //$changes = getFormChanges( $control, $_REQUEST['newControl'], $types, $columns );
  $Control->save($_REQUEST['newControl']);
  $refreshParent = true;
  $view = 'none';
} // end if action
?>

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

// Event scope actions, view permissions only required
if ( !canView('Events') ) {
  ZM\Warning('You do not have permission to view Events.');
  return;
}

if ( isset($_REQUEST['object']) and ( $_REQUEST['object'] == 'filter' ) ) {
  if ( $action == 'addterm' ) {
    $_REQUEST['filter'] = addFilterTerm($_REQUEST['filter'], $_REQUEST['line']);
  } elseif ( $action == 'delterm' ) {
    $_REQUEST['filter'] = delFilterTerm($_REQUEST['filter'], $_REQUEST['line']);
  } else if ( canEdit('Events') ) {

    require_once('includes/Filter.php');
    $filter = new ZM\Filter($_REQUEST['Id']);

    if ( $action == 'delete' ) {
      if ( !empty($_REQUEST['Id']) ) {
          if ( $filter->Background() ) {
            $filter->control('stop');
          }
          $filter->delete();

      } else {
        ZM\Error('No filter id passed when deleting');
      }
    } else if ( ( $action == 'Save' ) or ( $action == 'SaveAs' ) or ( $action == 'execute' ) ) {

      $sql = '';
      $_REQUEST['filter']['Query']['sort_field'] = validStr($_REQUEST['filter']['Query']['sort_field']);
      $_REQUEST['filter']['Query']['sort_asc'] = validStr($_REQUEST['filter']['Query']['sort_asc']);
      $_REQUEST['filter']['Query']['limit'] = validInt($_REQUEST['filter']['Query']['limit']);
      
      $_REQUEST['filter']['AutoCopy'] = empty($_REQUEST['filter']['AutoCopy']) ? 0 : 1;
      $_REQUEST['filter']['AutoMove'] = empty($_REQUEST['filter']['AutoMove']) ? 0 : 1;
      $_REQUEST['filter']['AutoArchive'] = empty($_REQUEST['filter']['AutoArchive']) ? 0 : 1;
      $_REQUEST['filter']['AutoVideo'] = empty($_REQUEST['filter']['AutoVideo']) ? 0 : 1;
      $_REQUEST['filter']['AutoUpload'] = empty($_REQUEST['filter']['AutoUpload']) ? 0 : 1;
      $_REQUEST['filter']['AutoEmail'] = empty($_REQUEST['filter']['AutoEmail']) ? 0 : 1;
      $_REQUEST['filter']['AutoMessage'] = empty($_REQUEST['filter']['AutoMessage']) ? 0 : 1;
      $_REQUEST['filter']['AutoExecute'] = empty($_REQUEST['filter']['AutoExecute']) ? 0 : 1;
      $_REQUEST['filter']['AutoDelete'] = empty($_REQUEST['filter']['AutoDelete']) ? 0 : 1;
      $_REQUEST['filter']['UpdateDiskSpace'] = empty($_REQUEST['filter']['UpdateDiskSpace']) ? 0 : 1;
      $_REQUEST['filter']['Background'] = empty($_REQUEST['filter']['Background']) ? 0 : 1;
      $_REQUEST['filter']['Concurrent'] = empty($_REQUEST['filter']['Concurrent']) ? 0 : 1;
      $changes = $filter->changes($_REQUEST['filter']);
      ZM\Logger::Debug('Changes: ' . print_r($changes,true));

      if ( $_REQUEST['Id'] and ( $action == 'Save' ) ) {
        if ( $filter->Background() )
          $filter->control('stop');
        $filter->save($changes);
      } else {

        if ( $action == 'execute' ) {
          if ( count($changes) ) {
            $filter->Name('_TempFilter'.time());
            $filter->Id(null);
          }
        } else if ( $action == 'SaveAs' ) {
					$filter->Id(null);
				}
        $filter->save($changes);

				// We update the request id so that the newly saved filter is auto-selected
				$_REQUEST['Id'] = $filter->Id();
      }
      if ( $filter->Background() )
        $filter->control('start');

      if ( $action == 'execute' ) {
        $filter->execute();
        if ( count($changes) )
          $filter->delete();

        $view = 'events';
      }

    } else if ( $action == 'control' ) {
      if ( $_REQUEST['command'] == 'start'
        or $_REQUEST['command'] == 'stop'
        or $_REQUEST['command'] == 'restart'
      ) {
        $filter->control($_REQUEST['command'], $_REQUEST['ServerId']);
      } else {
        ZM\Error('Invalid command for filter ('.$_REQUEST['command'].')');
      }
    } // end if save or execute
  } // end if canEdit(Events)
} // end if object == filter

?>

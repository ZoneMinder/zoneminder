<?php
//
// ZoneMinder web frames view file, $Date$, $Revision$
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

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

require_once('includes/Frame.php');
require_once('includes/Filter.php');

$eid = validInt($_REQUEST['eid']);
$Event = new ZM\Event($eid);

xhtmlHeaders(__FILE__, translate('Frames').' - '.$Event->Id());
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page" class="container-fluid p-3">
    <!-- Toolbar button placement and styling handled by bootstrap-tables -->
    <div id="toolbar">
      <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
    </div>

    <!-- Table styling handled by bootstrap-tables -->
    <div class="row justify-content-center table-responsive-sm">      
      <table
        id="framesTable"
        data-locale="<?php echo i18n() ?>"
        data-side-pagination="server"
        data-ajax="ajaxRequest"
        data-pagination="true"
        data-show-pagination-switch="true"
        data-page-list="[10, 25, 50, 100, 200, All]"
        data-search="true"
        data-cookie="true"
        data-cookie-id-table="zmFramesTable"
        data-cookie-expire="2y"
        data-remember-order="true"
        data-show-columns="true"
        data-show-export="true"
        data-toolbar="#toolbar"
        data-show-fullscreen="true"
        data-maintain-meta-data="true"
        data-buttons-class="btn btn-normal"
        data-detail-view="true"
        data-detail-formatter="detailFormatter"
        data-show-toggle="true"
        data-show-jump-to="true"
        data-show-refresh="true"
        class="table-sm table-borderless">

        <thead>
          <!-- Row styling is handled by bootstrap-tables -->
          <tr>
            <th class="px-3" data-align="center" data-sortable="false" data-field="EventId"><?php echo translate('EventId') ?></th>
            <th class="px-3" data-align="center" data-sortable="true" data-field="FrameId"><?php echo translate('FrameId') ?></th>
            <th class="px-3" data-align="center" data-sortable="true" data-field="Type"><?php echo translate('Type') ?></th>
            <th class="px-3" data-align="center" data-sortable="true" data-field="TimeStamp"><?php echo translate('TimeStamp') ?></th>
            <th class="px-3" data-align="center" data-sortable="true" data-field="Delta"><?php echo translate('TimeDelta') ?></th>
            <th class="px-3" data-align="center" data-sortable="true" data-field="Score"><?php echo translate('Score') ?></th>
            <th class="px-3" data-align="center" data-sortable="false" data-field="Thumbnail"><?php echo translate('Thumbnail') ?></th>
          </tr>
        </thead>
        <tbody>
          <!-- Row data populated via Ajax -->
        </tbody>
        </table>
      </div>
  </div>
<?php xhtmlFooter() ?>

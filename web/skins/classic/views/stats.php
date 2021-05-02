<?php
//
// ZoneMinder web stats view file, $Date$, $Revision$
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

if (!canView('Events')) {
  $view = 'error';
  return;
}

$eid = validInt($_REQUEST['eid']);
$fid = validInt($_REQUEST['fid']);

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Stats').' - '.$eid.' - '.$fid);
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page">
    <!-- Toolbar button placement and styling handled by bootstrap-tables -->
    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
    </div>

    <div id="content" class="row justify-content-center">
      <form name="contentForm" id="contentForm" method="get" action="?">
        <input type="hidden" name="view" value="none"/>
        <div class="table-responsive-sm">
          <?php echo getStatsTableHTML($eid, $fid) ?>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
<?php xhtmlFooter() ?>

<?php
//
// ZoneMinder web tags management view
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

// Listing tags needs what the event view needs; renaming and deleting need
// Events edit, as adding and removing them on an event does.
if (!(canView('Events') or canView('Snapshots'))) {
  $view = 'error';
  return;
}

xhtmlHeaders(__FILE__, translate('ManageTags'));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page" class="container-fluid">
    <h2><?php echo translate('ManageTags') ?></h2>

    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>"><i class="fa fa-refresh"></i></button>
    </div>

    <div id="content" class="row justify-content-center table-responsive-sm">
      <table id="tagsTable" class="table table-sm table-striped">
        <thead>
          <tr>
            <th><?php echo translate('Name') ?></th>
            <th><?php echo translate('Events') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody id="tagsBody"></tbody>
      </table>
    </div>
  </div>
<?php xhtmlFooter() ?>

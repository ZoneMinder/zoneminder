<?php
//
// ZoneMinder web function view file, $Date$, $Revision$
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

if ( !canEdit('Monitors') ) {
  $view = 'error';
  return;
}
?>

<div id="modalFunction" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
<form id="function_form" action="?view=function&action=function" method="post">
  <input type="hidden" name="mid"/>
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Function') ?> - <span id="function_monitor_name"></span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>
          <?php echo htmlSelect('newFunction', ZM\getMonitorFunctionTypes(), null, array('id'=>'newFunction')); ?>
          <label for="newEnabled"><?php echo translate('Enabled') ?></label>
          <input type="checkbox" name="newEnabled" id="newEnabled" value="1"/>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="funcSaveBtn btn btn-primary"><?php echo translate('Save') ?></button>
        <button type="button" class="funcCancelBtn btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
      </div>
</form>
    </div>
  </div>
</div>

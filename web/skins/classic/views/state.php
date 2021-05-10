<?php
//
// ZoneMinder web run state view file, $Date$, $Revision$
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

if ( !canEdit('System') ) {
  $view = 'error';
  return;
}
?>
<div id="modalState" class="modal fade">
  <form class="form-horizontal" name="contentForm" method="get" action="?view=state">
    <input type="hidden" name="view" value="state"/>
    <input type="hidden" name="action" value="state"/>
    <input type="hidden" name="apply" value="1"/>

    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h2 class="modal-title"><?php echo translate('RunState') ?></h2>
        </div>
        <div class="modal-body">

	        <div class="form-group">
	          <label for="runState" class="col-sm-3 control-label">Change State</label>
	          <div class="col-sm-9">
              <select id="runState" name="runState" class="form-control">
<?php 
if ( $running ) {
?>
                <option value="stop" selected="selected"><?php echo translate('Stop') ?></option>
                <option value="restart"><?php echo translate('Restart') ?></option>
<?php
} else {
?>
                <option value="start" selected="selected"><?php echo translate('Start') ?></option>
<?php
}
$states = dbFetchAll('SELECT * FROM States');
foreach ( $states as $state ) {
?>
                <option value="<?php echo validHtmlStr($state['Name']) ?>" <?php echo $state['IsActive'] ? 'selected="selected"' : '' ?>>
                <?php echo validHtmlStr($state['Name']); ?>
                </option>
<?php
}
?>
              </select>
	          </div><!--col-sm-9-->
	        </div><!--form-group-->
	        <div class="form-group">
            <label for="newState" class="col-sm-3 control-label"><?php echo translate('NewState') ?></label>
		        <div class="col-sm-9">
              <input class="form-control" type="text" id="newState"/>
		        </div>
	        </div>
        </div> <!-- modal-body -->
        <div class="modal-footer">
          <button class="btn btn-primary" type="button" id="btnApply"><?php echo translate('Apply') ?></button>
          <button class="btn btn-primary" type="button" id="btnSave" disabled><?php echo translate('Save') ?></button>
          <button class="btn btn-danger" type="button" id="btnDelete" disabled><?php echo translate('Delete') ?></button>
          <p class="pull-left hidden" id="pleasewait"><?php echo translate('PleaseWait') ?></p>
	      </div><!-- footer -->
      </div> <!-- content -->
    </div> <!-- dialog -->
  </form>
</div> <!-- state -->

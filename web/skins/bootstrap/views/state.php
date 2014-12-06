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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canEdit( 'System' ) )
{
    $view = "error";
    return;
}
$states = dbFetchAll( "select * from States" );

?>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
    <div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<p class="modal-title">State</p>
			</div>


      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
			<div class="row">
			<div class="col-md-6">
<?php if ( empty($_REQUEST['apply']) ) { ?>
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="state"/>
        <input type="hidden" name="apply" value="1"/>
				<div class="form-group">
					<label for="runState">Change State</label>
          <select class="form-control" name="runState" id="runState" onchange="checkState( this );">
<?php if ( $running ) { ?>
            <option value="stop" selected="selected"><?= $SLANG['Stop'] ?></option>
            <option value="restart"><?= $SLANG['Restart'] ?></option>
<?php } else { ?>
            <option value="start" selected="selected"><?= $SLANG['Start'] ?></option>
<?php } ?>
<?php foreach ( $states as $state ) { ?>
            <option value="<?= $state['Name'] ?>"><?= $state['Name'] ?></option>
<?php } ?>
          </select>
				</div> <!-- End .form-group -->
			</div> <!-- End .col-md-6 -->

			<div class="col-md-6">
				<div class="form-group">
					<label for="newState"> <?= $SLANG['NewState'] ?></label>
					<input class="form-control" type="text" id="newState" name="newState" value="" size="16" onchange="checkState( this );"/>
				</div> <!-- End .form-group -->
			</div> <!-- End .col-md-6 -->
		</div> <!-- End .row -->

        <div class="modal-footer">
          <input type="submit"  class="btn btn-default" value="<?= $SLANG['Apply'] ?>" id="btnStateApply" data-loading-text="Applying..." />
          <input type="button"  class="btn btn-default" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" disabled="disabled" onclick="deleteState( this );"/>
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <input type="button"  class="btn btn-default" name="saveBtn" value="<?= $SLANG['Save'] ?>" disabled="disabled" onclick="saveState( this );"/>
        </div> <!-- End .modal-footer -->

<?php } else { ?>
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="state"/>
        <input type="hidden" name="runState" value="<?= validHtmlStr($_REQUEST['runState']) ?>"/>
        <p><?= $SLANG['ApplyingStateChange'] ?></p>
        <p><?= $SLANG['PleaseWait'] ?></p>
<?php } ?>
      </form>
    </div>
	</div>
</div>

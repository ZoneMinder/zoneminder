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

if ( !canEdit('Monitors') ) return;

?>
<div id="modalFunction" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
    <form id="function_form" action="?view=function" method="post">
      <?php
      // We have to manually insert the csrf key into the form when using a modal generated via ajax call
      echo getCSRFinputHTML();
      ?>
      <input type="hidden" name="mid"/>
      <input type="hidden" name="action" value="save"/>
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Function') ?> - <span id="function_monitor_name"></span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group" id="FunctionFunction">
          <label for="newFunction"><?php echo translate('Function') ?></label>
          <?php echo htmlSelect('newFunction', ZM\getMonitorFunctionTypes(), null, array('id'=>'newFunction')); ?>
          <div id="function_help">
<?php
  foreach ( ZM\getMonitorFunctionTypes() as $fn => $translated ) {
    if ( isset($OLANG['FUNCTION_'.strtoupper($fn)]) ) {
      echo '<div class="form-text" id="'.$fn.'Help">'.$OLANG['FUNCTION_'.strtoupper($fn)]['Help'].'</div>';
    }
  }
?>
          </div>
        </div>
        <div class="form-group" id="FunctionAnalysisEnabled">
          <label for="newEnabled"><?php echo translate('Analysis Enabled') ?></label>
          <input type="checkbox" name="newEnabled" id="newEnabled" value="1"/>
<?php
  if ( isset($OLANG['FUNCTION_ANALYSIS_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_ANALYSIS_ENABLED']['Help'].'</div>';
  }
?>

        </div>
        <div class="form-group" id="FunctionDecodingEnabled">
          <label for="newDecodingEnabled"><?php echo translate('Decoding Enabled') ?></label>
          <input type="checkbox" name="newDecodingEnabled" id="newDecodingEnabled" value="1"/>
<?php
  if ( isset($OLANG['FUNCTION_DECODING_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_DECODING_ENABLED']['Help'].'</div>';
  }
?>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="funcSaveBtn btn btn-primary"><?php echo translate('Save') ?></button>
        <button type="button" class="funcCancelBtn btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
      </div>
    </form>
    </div>
  </div>
</div>

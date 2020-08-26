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

<div id="modalFunction-<?php echo $Monitor->Id() ?>" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Function').' - '.validHtmlStr($Monitor->Name()) ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>
          <select name="newFunction" id="funcSelect-<?php echo $monitor['Id'] ?>">
            <?php
            foreach ( getEnumValues('Monitors', 'Function') as $optFunction ) {
              $selected = ( $optFunction == $Monitor->Function() ) ? ' selected="selected"' : '';
              echo '<option value="' .$optFunction. '"'. $selected. '>' .translate('Fn'.$optFunction). '</option>'.PHP_EOL;
            }
            ?>
          </select>
          <label for="newEnabled"><?php echo translate('Enabled') ?></label>
          <input type="checkbox" name="newEnabled" id="newEnabled-<?php echo $monitor['Id'] ?>" value="1"<?php echo $Monitor->Enabled() ?' checked="checked"' : '' ?>/>
        </p>
      </div>
      <div class="modal-footer">
        <button data-mid="<?php echo $monitor['Id'] ?>" type="button" class="funcSaveBtn btn btn-primary"><?php echo translate('Save') ?></button>
        <button data-mid="<?php echo $monitor['Id'] ?>" type="button" class="funcCancelBtn btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
      </div>
    </div>
  </div>
</div>

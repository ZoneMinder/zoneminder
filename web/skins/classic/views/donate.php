<?php
//
// ZoneMinder web donate view file, $Date$, $Revision$
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

$options = array( 
  'go'      => translate('DonateYes'),
  'hour'    => translate('DonateRemindHour'),
  'day'     => translate('DonateRemindDay'),
  'week'    => translate('DonateRemindWeek'),
  'month'   => translate('DonateRemindMonth'),
  'never'   => translate('DonateRemindNever'),
  'already' => translate('DonateAlready'),
);

?>
<!-- Donate Modal -->
<div class="modal fade" id="donate" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="donateLabel">ZoneMinder - <?php echo translate('Donate') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form name="contentForm" id="contentForm" method="post" action="?">
          <input type="hidden" name="view" value="donate"/>
          <input type="hidden" name="action" value="donate"/>
          <p>
            <?php echo translate('DonateEnticement') ?>
          </p>
          <p>
            <?php echo buildSelect('option', $options); ?>
          </p>
          <div id="contentButtons">
            <button type="submit"><?php echo translate('Apply') ?></button>
            <button type="button" data-on-click="closeWindow"><?php echo translate('Close') ?></button>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Understood</button>
      </div>
    </div>
  </div>
</div>


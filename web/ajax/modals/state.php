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

if (!canEdit('System')) return;

$running = daemonCheck();

$content = '';
if ( $running ) {
  $content .= '<option value="stop" selected="selected">' .translate('Stop'). '</option>'.PHP_EOL;
  $content .= '<option value="restart">' .translate('Restart'). '</option>'.PHP_EOL;
} else {
  $content .= '<option value="start" selected="selected">' .translate('Start'). '</option>'.PHP_EOL;
}

$states = dbFetchAll('SELECT * FROM States');
foreach ( $states as $state ) {
  $selected = $state['IsActive'] ? 'selected="selected"' : '';
  
  $content .= '<option value="' .validHtmlStr($state["Name"]). '" ' .$selected. '>'.PHP_EOL;
  $content .= validHtmlStr($state['Name']).PHP_EOL;
  $content .= '</option>'.PHP_EOL;
}

// Monitors that are down, by the same rule the console uses to mark one in
// red: its capture daemon is not running, or it should be capturing all the
// time and reports no capture fps. An on-demand monitor with no viewer
// captures nothing, which is normal.
$downMonitors = array();
if ( $running ) {
  $monitors = dbFetchAll('SELECT M.Id, M.Name, M.Type, M.Enabled, M.Capturing, M.Deleted, S.Status, S.CaptureFPS
    FROM Monitors AS M
    LEFT JOIN Monitor_Status AS S ON S.MonitorId=M.Id
    ORDER BY M.Name');
  foreach ( $monitors as $monitor ) {
    if ( $monitor['Deleted'] or !$monitor['Enabled'] or ($monitor['Capturing'] == 'None') )
      continue;
    if ( $monitor['Type'] == 'WebSite' )
      continue;
    if ( !canView('Monitors', $monitor['Id']) )
      continue;
    if ( !$monitor['Status'] or ($monitor['Status'] == 'NotRunning') ) {
      $monitor['Reason'] = 'Not Running';
    } else if ( ($monitor['Capturing'] != 'Ondemand') and !(float)$monitor['CaptureFPS'] ) {
      $monitor['Reason'] = 'No capture FPS';
    } else {
      continue;
    }
    $downMonitors[] = $monitor;
  }
}

?>
<div id="modalState" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Run State')?> </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form name="contentForm" method="post" action="?view=state">
        <div class="modal-body">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <input type="hidden" name="action" value="state"/>
          <input type="hidden" name="apply" value="1"/>
          <div class="form-group">
            <label for="runState"><?php echo translate('Change State')?></label>
              <select id="runState" name="runState" class="form-control">
                <?php echo $content ?>
              </select>
          </div><!--form-group-->
<?php if ( count($downMonitors) ) { ?>
          <div class="form-group">
            <label><?php echo translate('Monitors') ?></label>
            <ul class="list-unstyled mb-0">
<?php foreach ( $downMonitors as $monitor ) {
  $statusText = $monitor['Reason'];
  $href = (canView('Stream', $monitor['Id']) ? '?view=watch&mid=' : '?view=monitor&mid=').validCardinal($monitor['Id']);
?>
              <li><a href="<?php echo $href ?>"><?php echo validHtmlStr($monitor['Name']) ?></a> <span class="text-danger"><?php echo validHtmlStr($statusText) ?></span></li>
<?php } ?>
            </ul>
          </div><!--form-group-->
<?php } ?>
          <div class="form-group">
            <label for="newState"><?php echo translate('NewState') ?></label>
              <input class="form-control" type="text" id="newState" maxlength="64"/>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="button" id="btnApply"><?php echo translate('Apply') ?></button>
          <button class="btn btn-primary" type="button" id="btnSave" disabled><?php echo translate('Save') ?></button>
          <button class="btn btn-danger" type="button" id="btnDelete" disabled><?php echo translate('Delete') ?></button>
          <p class="pull-left hidden" id="pleasewait"><?php echo translate('PleaseWait') ?></p>
        </div>
      </form>        
    </div>
  </div>
</div>

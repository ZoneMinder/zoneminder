<input type="hidden" name="newMonitor[Method]" value="<?php echo validHtmlStr($monitor->Method()) ?>"/>
<tr><td><?php echo translate('HostName') ?></td><td><input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($monitor->Host()) ?>" size="36"/></td></tr>
<tr><td><?php echo translate('Port') ?></td><td><input type="number" name="newMonitor[Port]" value="<?php echo validHtmlStr($monitor->Port()) ?>" size="6"/></td></tr>
<tr><td><?php echo translate('Path') ?></td><td><input type="hidden" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" size="36"/></td></tr>
<input type="hidden" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>"/>
<input type="hidden" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>"/>
<input type="hidden" name="newMonitor[Options]" value="<?php echo validHtmlStr($monitor->Options()) ?>"/>
<tr>
  <td><?php echo translate('TargetColorspace') ?></td>
  <td><?php echo htmlSelect( 'newMonitor[Colours]', $Colours, $monitor->Colours() ); ?></td></tr>
<tr>
  <td><?php echo translate('CaptureWidth') ?> (<?php echo translate('Pixels') ?>)</td>
  <td><input type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($monitor->Width()) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td>
</tr>
<tr>
  <td><?php echo translate('CaptureHeight') ?> (<?php echo translate('Pixels') ?>)</td>
  <td><input type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($monitor->Height()) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td>
</tr>
<tr><td><?php echo translate('PreserveAspect') ?></td><td><input type="checkbox" name="preserveAspectRatio" value="1"/></td></tr> 
<tr><td><?php echo translate('Orientation') ?></td><td><?php echo htmlselect( 'newMonitor[Orientation]', $orientations, $monitor->Orientation() );?></td></tr>
<input type="hidden" name="newMonitor[Deinterlacing]" value="<?php echo validHtmlStr($monitor->Deinterlacing()) ?>"/>
<input type="hidden" name="newMonitor[RTSPDescribe]" value="<?php echo validHtmlStr($monitor->RTSPDescribe()) ?>"/>

<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Controllable') ?></label><div class="col-sm-3"><input class="form-control" type="checkbox" name="newMonitor[Controllable]" value="1"<?php if ( !empty($newMonitor['Controllable']) ) { ?> checked="checked"<?php } ?>/></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('ControlType') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[ControlId]", $controlTypes, 'loadLocations( this )' ); ?><?php if ( canEdit( 'Control' ) ) { ?>&nbsp;<a href="#" onclick="createPopup( '?view=controlcaps', 'zmControlCaps', 'controlcaps' );"><?php echo translate('Edit') ?></a><?php } ?></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('ControlDevice') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[ControlDevice]" value="<?php echo validHtmlStr($newMonitor['ControlDevice']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('ControlAddress') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[ControlAddress]" value="<?php echo validHtmlStr($newMonitor['ControlAddress']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('AutoStopTimeout') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[AutoStopTimeout]" value="<?php echo validHtmlStr($newMonitor['AutoStopTimeout']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('TrackMotion') ?></label><div class="col-sm-3"><input class="form-control" type="checkbox" name="newMonitor[TrackMotion]" value="1"<?php if ( !empty($newMonitor['TrackMotion']) ) { ?> checked="checked"<?php } ?>/></div>
</div>
<?php
$return_options = array(
		'-1' => translate('None'),
		'0' => translate('Home'),
		'1' => translate('Preset')." 1",
		);
?>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('TrackDelay') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[TrackDelay]" value="<?php echo validHtmlStr($newMonitor['TrackDelay']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('ReturnLocation') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[ReturnLocation]", $return_options ); ?></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('ReturnDelay') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[ReturnDelay]" value="<?php echo validHtmlStr($newMonitor['ReturnDelay']) ?>"></div>
</div>

<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('EventPrefix') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[EventPrefix]" value="<?php echo validHtmlStr($newMonitor['EventPrefix']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Sectionlength') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[SectionLength]" value="<?php echo validHtmlStr($newMonitor['SectionLength']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('FrameSkip') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[FrameSkip]" value="<?php echo validHtmlStr($newMonitor['FrameSkip']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('MotionFrameSkip') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[MotionFrameSkip]" value="<?php echo validHtmlStr($newMonitor['MotionFrameSkip']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('AnalysisUpdateDelay') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[AnalysisUpdateDelay]" value="<?php echo validHtmlStr($newMonitor['AnalysisUpdateDelay']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('FPSReportInterval') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[FPSReportInterval]" value="<?php echo validHtmlStr($newMonitor['FPSReportInterval']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('DefaultView') ?></label><div class="col-sm-3"><select name="newMonitor[DefaultView]" class="form-control">
<?php foreach ( getEnumValues( 'Monitors', 'DefaultView' ) as $opt_view ) {
	if ( $opt_view == 'Control' && ( !ZM_OPT_CONTROL || !$monitor['Controllable'] ) )
		continue;
	?>
		<option value="<?php echo $opt_view ?>"<?php if ( $opt_view == $newMonitor['DefaultView'] ) { ?> selected="selected"<?php } ?>><?php echo $opt_view ?></option>
		<?php } ?>
		</select></div>
		</div>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('DefaultRate') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[DefaultRate]", $rates, '', 'form-control' ); ?></div>
		</div>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('DefaultScale') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[DefaultScale]", $scales, '', 'form-control' ); ?></div>
		</div>
		<?php
		if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
{
	?>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('SignalCheckColour') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[SignalCheckColour]" value="<?php echo validHtmlStr($newMonitor['SignalCheckColour']) ?>" onchange="$('SignalCheckSwatch').setStyle( 'backgroundColor', this.value )"/><span id="SignalCheckSwatch" class="swatch" style="background-color: <?php echo $newMonitor['SignalCheckColour'] ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
		</div>
		<?php
}
?>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('WebColour') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[WebColour]" value="<?php echo validHtmlStr($newMonitor['WebColour']) ?>" onchange="$('WebSwatch').setStyle( 'backgroundColor', this.value )"/><span id="WebSwatch" class="swatch" style="background-color: <?php echo validHtmlStr($newMonitor['WebColour']) ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
</div>


<div class="form-group">
<div class="col-sm-3 col-sm-offset-3">
<div class="checkbox">
<label>
<input type="checkbox" name="newMonitor[Exif]" value="1"<?php if ( !empty($newMonitor['Exif']) ) { ?> checked="checked"<?php } ?>/>
<?php echo translate('Exif') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_EXIF', 'zmOptionHelp', 'optionhelp', '?' ) ?>)
</label>
</div>
</div>
</div>

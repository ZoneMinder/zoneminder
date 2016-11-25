<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Name') ?></label>
<div class="col-sm-3">
<input class="form-control" type="text" name="newMonitor[Name]" value="<?php echo validHtmlStr($newMonitor['Name']) ?>" size="16"/>
</div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Server') ?></label>
<div class="col-sm-3">

<?php 
$servers = array(''=>'None');
$result = dbQuery( 'SELECT * FROM Servers ORDER BY Name');
$results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Server' );
foreach ( $results as $row => $server_obj ) {
	$servers[$server_obj->Id] = $server_obj->Name();
} ?>
<?php echo buildSelect( "newMonitor[ServerId]", $servers, '', 'form-control' ); ?>
</div>

</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('SourceType') ?></label>
<div class="col-sm-3">
<?php echo buildSelect( "newMonitor[Type]", $sourceTypes, '', 'form-control' ); ?>
</div>
</div>

<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Function') ?></label>
<div class="col-sm-3">
<select class="form-control" name="newMonitor[Function]">
<?php foreach ( getEnumValues( 'Monitors', 'Function' ) as $optFunction ) { ?>
		<option value="<?php echo $optFunction ?>"<?php if ( $optFunction == $newMonitor['Function'] ) { ?> selected="selected"<?php } ?>><?php echo translate('Fn'.$optFunction) ?></option>
<?php } ?>
</select>
</div>
</div>

<div class="form-group">
<div class="col-sm-3 col-sm-offset-3">
<div class="checkbox">
<label>
<input type="checkbox" name="newMonitor[Enabled]" value="1"<?php if ( !empty($newMonitor['Enabled']) ) { ?> checked="checked"<?php } ?>/>
<?php echo translate('Enabled') ?>
</label>
</div>
</div>
</div>

<div class="form-group">

<label class="col-sm-3 control-label"><?php echo translate('LinkedMonitors') ?></label>

<label class="col-sm-3 control-label">
<select class="form-control" name="monitorIds" size="4" multiple="multiple" onchange="updateLinkedMonitors( this )">
<?php
$monitors = dbFetchAll( "select Id,Name from Monitors order by Sequence asc" );
if ( !empty($newMonitor['LinkedMonitors']) ) {
	$monitorIds = array_flip( explode( ',', $newMonitor['LinkedMonitors'] ) );
} else {
	$monitorIds = array();
}
foreach ( $monitors as $monitor ) {
	if ( (empty($newMonitor['Id']) || ($monitor['Id'] != $newMonitor['Id'])) && visibleMonitor( $monitor['Id'] ) ) { ?>
			<option value="<?php echo $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?php echo validHtmlStr($monitor['Name']) ?></option>
	<?php }
} ?>
</select>
</label>


</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('AnalysisFPS') ?></label>
<div class="col-sm-3">
<input class="form-control" type="text" name="newMonitor[AnalysisFPS]" value="<?php echo validHtmlStr($newMonitor['AnalysisFPS']) ?>" size="6"/>
</div>
</div>
<?php
if ( $newMonitor['Type'] != "Local" && $newMonitor['Type'] != "File" ) { ?>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('MaximumFPS') ?>&nbsp;(<?php echo makePopupLink('?view=optionhelp&amp;option=OPTIONS_MAXFPS', 'zmOptionHelp', 'optionhelp', '?' ) ?>)</label>
		<div class="col-sm-3">
		<input class="form-control" type="text" onclick="document.getElementById('newMonitor[MaxFPS]').innerHTML= ' CAUTION: See the help text'" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($newMonitor['MaxFPS']) ?>" size="5"/><span id="newMonitor[MaxFPS]" style="color:red"></span>
		</div>
		</div>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('AlarmMaximumFPS') ?>&nbsp;(<?php echo makePopupLink('?view=optionhelp&amp;option=OPTIONS_MAXFPS', 'zmOptionHelp', 'optionhelp', '?' ) ?>)</label>
		<div class="col-sm-3">
		<input class="form-control" type="text" onclick="document.getElementById('newMonitor[AlarmMaxFPS]').innerHTML= ' CAUTION: See the help text'" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" size="5"/><span id="newMonitor[AlarmMaxFPS]" style="color:red"></span>
		</div>
		</div>
<?php } else { ?>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('MaximumFPS') ?></label>
		<div class="col-sm-3">
		<input class="form-control" type="text" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($newMonitor['MaxFPS']) ?>" size="5"/>
		</div>
		</div>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('AlarmMaximumFPS') ?></label>
		<div class="col-sm-3">
		<input class="form-control" type="text" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" size="5"/>
		</div>
		</div>
<?php } if ( ZM_FAST_IMAGE_BLENDS ) { ?>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('RefImageBlendPct') ?></label>
		<div class="col-sm-3">
		<select class="form-control" name="newMonitor[RefBlendPerc]"><?php foreach ( $fastblendopts as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['RefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select>
		</div>
		</div>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('AlmRefImageBlendPct') ?></label>
		<div class="col-sm-3">
		<select class="form-control" name="newMonitor[AlarmRefBlendPerc]"><?php foreach ( $fastblendopts_alarm as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['AlarmRefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select>
		</div>
		</div>
<?php } else { ?>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('RefImageBlendPct') ?></label>
		<div class="col-sm-3">
		<input class="form-control" type="text" name="newMonitor[RefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['RefBlendPerc']) ?>" size="4"/>
		</div>
		</div>
		<div class="form-group">
		<label class="col-sm-3 control-label"><?php echo translate('AlarmRefImageBlendPct') ?></label>
		<div class="col-sm-3">
		<input class="form-control" type="text" name="newMonitor[AlarmRefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['AlarmRefBlendPerc']) ?>" size="4"/>
		</div>
		</div>
<?php } ?>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Triggers') ?></label>
<div class="col-sm-3">

<?php
$optTriggers = getSetValues( 'Monitors', 'Triggers' );
$breakCount = (int)(ceil(count($optTriggers)));
$breakCount = min( 3, $breakCount );
$optCount = 0;
foreach( $optTriggers as $optTrigger ) {
	if ( !ZM_OPT_X10 && $optTrigger == 'X10' )
		continue;
	if ( $optCount && ($optCount%$breakCount == 0) )
		echo "</br>";
?>
		<input class="form-control" type="checkbox" name="newMonitor[Triggers][]" value="<?php echo $optTrigger ?>"<?php if ( isset($newMonitor['Triggers']) && in_array( $optTrigger, $newMonitor['Triggers'] ) ) { ?> checked="checked"<?php } ?>/>&nbsp;<?php echo $optTrigger ?>
		<?php
		$optCount ++;
}
if ( !$optCount ) {
		echo '<em>'.translate('NoneAvailable').'</em>';
} ?>
</div>

</div>

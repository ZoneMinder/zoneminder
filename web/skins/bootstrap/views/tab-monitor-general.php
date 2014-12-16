<div role="tabpanel" class="tab-pane active" id="general">

	<div class="form-group">
		<label for="Name"><?= $SLANG['Name'] ?></label>
		<input type="text" id="Name" class="form-control" name="newMonitor[Name]" placeholder="Monitor-Name" />
	</div>
	<div class="form-group">
		<label for="SourceType"><?= $SLANG['SourceType'] ?></label>
		<select name="newMonitor[Type]" id="SourceType" class="form-control">
			<option value="Local">Local</option>
			<option value="Remote">Remote</option>
			<option value="File">File</option>
			<option value="Ffmpeg">Ffmpeg</option>
			<option value="Libvlc">Libvlc</option>
			<option value="cURL">cURL (HTTP(S) only)</option>
		</select>
	</div>
	<div class="form-group">
		<label for="Function"><?= $SLANG['Function'] ?></label>
		<select class="form-control" id="Function" name="newMonitor[Function]">
			<option value="None">None</option>
			<option value="Monitor" selected="selected">Monitor</option>
			<option value="Modect">Modect</option>
			<option value="Record">Record</option>
			<option value="Mocord">Mocord</option>
			<option value="Nodect">Nodect</option>
		</select>
	</div>
	<div class="checkbox">
		<label>
			<input type="checkbox" id="Enabled" class="form-control" name="newMonitor[Enabled]" value="1"<?php if ( !empty($newMonitor['Enabled']) ) { ?> checked="checked"<?php } ?>/>
			Enabled
		</label>
	</div>
	<div class="form-group">
		<label for="MaxFPS"><?= $SLANG['MaximumFPS'] ?></label>
		<input type="number" id="MaxFPS" class="form-control" name="newMonitor[MaxFPS]" value="<?= validHtmlStr($newMonitor['MaxFPS']) ?>" />
	</div>
	<div class="form-group">
		<label for="AlarmMaxFPS"><?= $SLANG['AlarmMaximumFPS'] ?></label>
		<input type="number" id="AlarmMaxFPS" class="form-control" name="newMonitor[AlarmMaxFPS]" value="<?= validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" />
	</div>
<?php
	if ( ZM_FAST_IMAGE_BLENDS )
        {
?>
	<div class="form-group">
		<label for=""><?= $SLANG['RefImageBlendPct'] ?></label>
<select class="form-control" name="newMonitor[RefBlendPerc]"><?php foreach ( $fastblendopts as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['RefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></div>
	<div class="form-group"><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></label>
<select class="form-control" name="newMonitor[AlarmRefBlendPerc]"><?php foreach ( $fastblendopts_alarm as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['AlarmRefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></div>
<?php
	} else {
?>
	<div class="form-group">
		<label for=""><?= $SLANG['RefImageBlendPct'] ?></label>
		<input type="text" class="form-control" name="newMonitor[RefBlendPerc]" value="<?= validHtmlStr($newMonitor['RefBlendPerc']) ?>" />
	</div>
	<div class="form-group"><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></label>
		<input type="text" class="form-control" name="newMonitor[AlarmRefBlendPerc]" value="<?= validHtmlStr($newMonitor['AlarmRefBlendPerc']) ?>" />
	</div>
<?php
        }
?>
</div>

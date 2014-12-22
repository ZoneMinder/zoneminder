<div role="tabpanel" class="tab-pane active" id="general">

<div class="row">

	<div class="col-md-4">

	<div class="form-group">
		<label for="Name"><?= $SLANG['Name'] ?></label>
		<input type="text" id="Name" class="form-control" ng-model="monitor.Name" placeholder="Monitor-Name" required />
	</div>
	<div class="form-group">
		<label for="Type"><?= $SLANG['SourceType'] ?></label>
		<select ng-model="monitor.Type" id="Type" class="form-control" required>
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
		<select class="form-control" id="Function" ng-model="monitor.Function" required>
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
			<input type="checkbox" id="Enabled" ng-model="monitor.Enabled" value="1"<?php if ( !empty($newMonitor['Enabled']) ) { ?> checked="checked"<?php } ?>/>
			Enabled
		</label>
	</div>
<?php
	if ( ZM_FAST_IMAGE_BLENDS )
        {
?>
	<div class="form-group">
		<label for=""><?= $SLANG['RefImageBlendPct'] ?></label>
<select class="form-control" ng-model="monitor.RefBlendPerc"><?php foreach ( $fastblendopts as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['RefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></div>
	<div class="form-group">
		<label for=""><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></label>
<select class="form-control" ng-model="monitor.AlarmRefBlendPerc"><?php foreach ( $fastblendopts_alarm as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['AlarmRefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></div>
<?php
	} else {
?>
	<div class="form-group">
		<label for=""><?= $SLANG['RefImageBlendPct'] ?></label>
		<input type="text" class="form-control" ng-model="monitor.RefBlendPerc" value="6" />
	</div>
	<div class="form-group">
		<label for=""><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></label>
		<input type="text" class="form-control" ng-model="monitor.AlarmRefBlendPerc" value="6" />
	</div>
<?php
        }
?>
	<div class="form-group" ng-show="monitor.sourceType == 'Local'">
		<label for="MaxFPS"><?= $SLANG['MaximumFPS'] ?></label>
		<input type="text" id="MaxFPS" class="form-control" ng-model="monitor.MaxFPS" />
	</div>
	<div class="form-group" ng-show="monitor.sourceType == 'Local'">
		<label for="AlarmMaxFPS"><?= $SLANG['AlarmMaximumFPS'] ?></label>
		<input type="text" id="AlarmMaxFPS" class="form-control" ng-model="monitor.AlarmMaxFPS" />
	</div>
	</div> <!-- End .col-md-6 -->

	<div class="col-md-4">
		<div class="form-group">
 			<label for=""><?= "Target Colorspace" ?></label>
			<select required class="form-control" ng-model="monitor.Colours">
				<?php foreach ( $Colours as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Colours'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?>
			</select>
		</div>

		<div class="form-group">
 			<label for=""><?= $SLANG['CaptureWidth'] ?> (<?= $SLANG['Pixels'] ?>)</label>
			<input class="form-control" type="text" ng-model="monitor.Width" placeholder="704" />
		</div>

		<div class="form-group">
 			<label for=""><?= $SLANG['CaptureHeight'] ?> (<?= $SLANG['Pixels'] ?>)</label>
			<input class="form-control" type="text" ng-model="monitor.Height" placeholder="480" />
		</div>

		<div class="checkbox">
 			<label>
				<input type="checkbox" ng-model="monitor.preserveAspectRatio" value="1"/>
				<?= $SLANG['PreserveAspect'] ?>
			</label>
		</div>

		<div class="form-group">
 			<label for=""><?= $SLANG['Orientation'] ?></label>
			<select class="form-control" ng-model="monitor.Orientation" required>
				<option value="0">Normal</option>
				<option value="90">Rotate Right</option>
				<option value="180">Inverted</option>
				<option value="270">Rotate Left</option>
				<option value="hori">Flipped Horizontally</option>
				<option value="vert">Flipped Vertically</option>
			</select>
		</div>
	</div> <!-- End .col-md-6 -->

	<div class="col-md-4">


	<?php include("tab-monitor-local.php"); ?>
	<?php include("tab-monitor-curl.php"); ?>
	<?php include("tab-monitor-ffmpeg-vlc.php"); ?>
	<?php include("tab-monitor-remote.php"); ?>

	</div> <!-- End .col-md-4 -->
</div> <!-- End .row -->


</div>

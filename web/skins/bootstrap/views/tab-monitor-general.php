<div role="tabpanel" class="tab-pane active" id="general">

<div class="row">

	<div class="col-md-4">

	<div class="form-group">
		<label for="Name"><?= $SLANG['Name'] ?></label>
		<input type="text" id="Name" class="form-control" ng-model="Name" placeholder="Monitor-Name" />
	</div>
	<div class="form-group">
		<label for="SourceType"><?= $SLANG['SourceType'] ?></label>
		<select ng-model="sourceType" id="SourceType" class="form-control">
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
		<select class="form-control" id="Function" ng-model="Function">
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
			<input type="checkbox" id="Enabled" ng-model="Enabled" value="1"<?php if ( !empty($newMonitor['Enabled']) ) { ?> checked="checked"<?php } ?>/>
			Enabled
		</label>
	</div>
<?php
	if ( ZM_FAST_IMAGE_BLENDS )
        {
?>
	<div class="form-group">
		<label for=""><?= $SLANG['RefImageBlendPct'] ?></label>
<select class="form-control" ng-model="RefBlendPerc"><?php foreach ( $fastblendopts as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['RefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></div>
	<div class="form-group">
		<label for=""><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></label>
<select class="form-control" ng-model="AlarmRefBlendPerc"><?php foreach ( $fastblendopts_alarm as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['AlarmRefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></div>
<?php
	} else {
?>
	<div class="form-group">
		<label for=""><?= $SLANG['RefImageBlendPct'] ?></label>
		<input type="text" class="form-control" ng-model="RefBlendPerc" value="6" />
	</div>
	<div class="form-group">
		<label for=""><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></label>
		<input type="text" class="form-control" ng-model="AlarmRefBlendPerc" value="6" />
	</div>
<?php
        }
?>
	<div class="form-group" ng-show="sourceType == 'Local'">
		<label for="MaxFPS"><?= $SLANG['MaximumFPS'] ?></label>
		<input type="number" id="MaxFPS" class="form-control" ng-model="MaxFPS" />
	</div>
	<div class="form-group" ng-show="sourceType == 'Local'">
		<label for="AlarmMaxFPS"><?= $SLANG['AlarmMaximumFPS'] ?></label>
		<input type="number" id="AlarmMaxFPS" class="form-control" ng-model="AlarmMaxFPS" />
	</div>
	</div> <!-- End .col-md-6 -->

	<div class="col-md-4">
		<div class="form-group">
 			<label for=""><?= "Target Colorspace" ?></label>
			<select class="form-control" name="newMonitor[Colours]"><?php foreach ( $Colours as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Colours'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
		</div>

		<div class="form-group">
 			<label for=""><?= $SLANG['CaptureWidth'] ?> (<?= $SLANG['Pixels'] ?>)</label>
			<input class="form-control" type="number" name="newMonitor[Width]" value="<?= validHtmlStr($newMonitor['Width']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/>
		</div>

		<div class="form-group">
 			<label for=""><?= $SLANG['CaptureHeight'] ?> (<?= $SLANG['Pixels'] ?>)</label>
			<input class="form-control" type="number" name="newMonitor[Height]" value="<?= validHtmlStr($newMonitor['Height']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/>
		</div>

		<div class="checkbox">
 			<label>
				<input type="checkbox" name="preserveAspectRatio" value="1"/>
				<?= $SLANG['PreserveAspect'] ?>
			</label>
		</div>

		<div class="form-group">
 			<label for=""><?= $SLANG['Orientation'] ?></label>
			<select class="form-control" name="newMonitor[Orientation]"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Orientation'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
		</div>
	</div> <!-- End .col-md-6 -->

	<div class="col-md-4">

		<div ng-show="sourceType == 'Remote'">
			<div class="form-group">
				<label for=""><?= $SLANG['RemoteProtocol'] ?></label>
				<select name="newMonitor[Protocol]" id="newMonitor[Protocol]" onchange="updateMethods( this )">
					<option value="http">HTTP</option>
					<option value="rtsp">RTSP</option>
				</select>
			</div>
			<div class="form-group">
				<label for=""><?= $SLANG['RemoteMethod'] ?></label>
<?php
            if ( empty($newMonitor['Protocol']) || $newMonitor['Protocol'] == "http" )
            {
?>
            <?= buildSelect( "newMonitor[Method]", $httpMethods ); ?>
<?php
            }
            else
            {
?>
            <?= buildSelect( "newMonitor[Method]", $rtspMethods ); ?>
<?php
            }
?>
			</div>
			<div class="form-group">
				<label><?= $SLANG['RemoteHostName'] ?></label>
				<input type="text" class="form-control" name="newMonitor[Host]" value="<?= validHtmlStr($newMonitor['Host']) ?>" size="36"/>
			</div>
			<div class="form-group">
				<label for=""><?= $SLANG['RemoteHostPort'] ?></label>
				<input type="number" class="form-control" name="newMonitor[Port]" value="<?= validHtmlStr($newMonitor['Port']) ?>" size="6"/>
			</div>
			<div class="form-group">
				<label><?= $SLANG['RemoteHostPath'] ?></label>
				<input type="text" class="form-control" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/>
			</div>
	</div> <!-- End Remote -->

	<?php include("tab-monitor-local.php"); ?>

	</div> <!-- End .col-md-4 -->
</div> <!-- End .row -->


</div>

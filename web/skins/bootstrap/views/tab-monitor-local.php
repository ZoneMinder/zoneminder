<div ng-show="monitor.sourceType == 'Local'">

	<div class="form-group">
		<label for=""><?= $SLANG['DevicePath'] ?></label>
		<input type="text" class="form-control" ng-model="monitor.Device" ng-required="monitor.sourceType == 'Local'"/>
	</div>

	<div class="form-group">
		<label for=""><?= $SLANG['CaptureMethod'] ?></label>
		<?= buildSelect( "newMonitor[Method]", $localMethods, "submitTab( '$tab' )" ); ?>
	</div>
<?php
            if ( ZM_HAS_V4L1 && $newMonitor['Method'] == 'v4l1' )
            {
?>

<div class="form-group">
            <label for=""><?= $SLANG['DeviceChannel'] ?></label>
	<select ng-model="monitor.Channel"><?php foreach ( $v4l1DeviceChannels as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
</div>

<div class="form-group">
            <label for=""><?= $SLANG['DeviceFormat'] ?></label>
	<select ng-model="monitor.Format"><?php foreach ( $v4l1DeviceFormats as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
</div>

<div class="form-group">
            <label for=""><?= $SLANG['CapturePalette'] ?></label>
	<select ng-model="monitor.Palette"><?php foreach ( $v4l1LocalPalettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
</div>
<?php
            }
            else
            {
?>

<div class="form-group">
            <label for=""><?= $SLANG['DeviceChannel'] ?></label>
	<select ng-model="monitor.Channel"><?php foreach ( $v4l2DeviceChannels as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
</div>

<div class="form-group">
            <label for=""><?= $SLANG['DeviceFormat'] ?></label>
	<select ng-model="monitor.Format"><?php foreach ( $v4l2DeviceFormats as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
</div>

<div class="form-group">
            <label for=""><?= $SLANG['CapturePalette'] ?></label>
	<select ng-model="monitor.Palette"><?php foreach ( $v4l2LocalPalettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select>
</div>
<?php
            }
?>
			<div class="radio">
			<?= $SLANG['V4LMultiBuffer'] ?>

		<label>
			<input type="radio" ng-model="monitor.V4LMultiBuffer" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $newMonitor['V4LMultiBuffer'] == 1 ? 'checked="checked"' : '' ) ?>/>
			Yes
		</label>
		<label>
			<input type="radio" ng-model="monitor.V4LMultiBuffer" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $newMonitor['V4LMultiBuffer'] == 0 ? 'checked="checked"' : '' ) ?>/>
			No
		</label>
		<label>
			<input type="radio" ng-model="monitor.V4LMultiBuffer" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( empty($newMonitor['V4LMultiBuffer']) ? 'checked="checked"' : '' ) ?>/>
			Use Config Value
		</label>
			</div>
			

	<div class="form-group">
		<label for=""><?= $SLANG['V4LCapturesPerFrame'] ?></label>
		<input type="text" ng-model="monitor.V4LCapturesPerFrame" />
	</div>
</div> <!-- End local -->

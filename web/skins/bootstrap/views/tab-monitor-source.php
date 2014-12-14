<div role="tabpanel" class="form-horizontal tab-pane" id="source">

<?php
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?= $SLANG['DevicePath'] ?></td><td><input type="text" name="newMonitor[Device]" value="<?= validHtmlStr($newMonitor['Device']) ?>" size="24"/></td></tr>
            <tr><td><?= $SLANG['CaptureMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $localMethods, "submitTab( '$tab' )" ); ?></td></tr>
<?php
            if ( ZM_HAS_V4L1 && $newMonitor['Method'] == 'v4l1' )
            {
?>
            <tr><td><?= $SLANG['DeviceChannel'] ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l1DeviceChannels as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['DeviceFormat'] ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l1DeviceFormats as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['CapturePalette'] ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l1LocalPalettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?= $SLANG['DeviceChannel'] ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l2DeviceChannels as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['DeviceFormat'] ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l2DeviceFormats as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['CapturePalette'] ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l2LocalPalettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
            }
?>
			<tr><td><?= $SLANG['V4LMultiBuffer'] ?></td><td>
				<input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $newMonitor['V4LMultiBuffer'] == 1 ? 'checked="checked"' : '' ) ?>/>
				<label for="newMonitor[V4LMultiBuffer]1">Yes</label>
				<input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $newMonitor['V4LMultiBuffer'] == 0 ? 'checked="checked"' : '' ) ?>/>
				<label for="newMonitor[V4LMultiBuffer]0">No</label>
				<input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( empty($newMonitor['V4LMultiBuffer']) ? 'checked="checked"' : '' ) ?>/>
				<label for="newMonitor[V4LMultiBuffer]">Use Config Value</label>
			</td></tr>
			<tr><td><?= $SLANG['V4LCapturesPerFrame'] ?></td><td><input type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo $newMonitor['V4LCapturesPerFrame'] ?>"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Remote" )
        {
?>
            <tr><td><?= $SLANG['RemoteProtocol'] ?></td><td><?= buildSelect( "newMonitor[Protocol]", $remoteProtocols, "updateMethods( this )" ); ?></td></tr>
<?php
            if ( empty($newMonitor['Protocol']) || $newMonitor['Protocol'] == "http" )
            {
?>
            <tr><td><?= $SLANG['RemoteMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $httpMethods ); ?></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?= $SLANG['RemoteMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
<?php
            }
?>
            <tr><td><?= $SLANG['RemoteHostName'] ?></td><td><input type="text" name="newMonitor[Host]" value="<?= validHtmlStr($newMonitor['Host']) ?>" size="36"/></td></tr>
            <tr><td><?= $SLANG['RemoteHostPort'] ?></td><td><input type="text" name="newMonitor[Port]" value="<?= validHtmlStr($newMonitor['Port']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['RemoteHostPath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "File" )
        {
?>
            <tr><td><?= $SLANG['SourcePath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "cURL" )
        {
?>
            <tr><td><?= "URL" ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?= "Username" ?></td><td><input type="text" name="newMonitor[User]" value="<?= validHtmlStr($newMonitor['User']) ?>" size="12"/></td></tr>
            <tr><td><?= "Password" ?></td><td><input type="text" name="newMonitor[Pass]" value="<?= validHtmlStr($newMonitor['Pass']) ?>" size="12"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Ffmpeg" || $newMonitor['Type'] == "Libvlc")
        {
?>
			<tr><td><?= $SLANG['SourcePath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?= $SLANG['RemoteMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
			<tr><td><?= $SLANG['Options'] ?>&nbsp;(<?= makePopupLink( '?view=optionhelp&amp;option=OPTIONS_'.strtoupper($newMonitor['Type']), 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" name="newMonitor[Options]" value="<?= validHtmlStr($newMonitor['Options']) ?>" size="36"/></td></tr>
<?php
        }
?>
            <tr><td><?= "Target Colorspace" ?></td><td><select name="newMonitor[Colours]"><?php foreach ( $Colours as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Colours'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['CaptureWidth'] ?> (<?= $SLANG['Pixels'] ?>)</td><td><input type="text" name="newMonitor[Width]" value="<?= validHtmlStr($newMonitor['Width']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?= $SLANG['CaptureHeight'] ?> (<?= $SLANG['Pixels'] ?>)</td><td><input type="text" name="newMonitor[Height]" value="<?= validHtmlStr($newMonitor['Height']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?= $SLANG['PreserveAspect'] ?></td><td><input type="checkbox" name="preserveAspectRatio" value="1"/></td></tr> 
            <tr><td><?= $SLANG['Orientation'] ?></td><td><select name="newMonitor[Orientation]"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Orientation'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        if ( $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?= "Deinterlacing" ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts_v4l2 as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        } else {
?>
            <tr><td><?= "Deinterlacing" ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        }
?>
</div>

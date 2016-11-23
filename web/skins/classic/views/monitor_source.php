<?php
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?php echo translate('DevicePath') ?></td><td><input type="text" name="newMonitor[Device]" value="<?php echo validHtmlStr($newMonitor['Device']) ?>" size="24"/></td></tr>
            <tr><td><?php echo translate('CaptureMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $localMethods, "submitTab( '$tab' )" ); ?></td></tr>
<?php
            if ( ZM_HAS_V4L1 && $newMonitor['Method'] == 'v4l1' )
            {
?>
            <tr><td><?php echo translate('DeviceChannel') ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l1DeviceChannels as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('DeviceFormat') ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l1DeviceFormats as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('CapturePalette') ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l1LocalPalettes as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?php echo translate('DeviceChannel') ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l2DeviceChannels as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('DeviceFormat') ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l2DeviceFormats as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('CapturePalette') ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l2LocalPalettes as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
            }
?>
      <tr><td><?php echo translate('V4LMultiBuffer') ?></td><td>
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $newMonitor['V4LMultiBuffer'] == 1 ? 'checked="checked"' : '' ) ?>/>
        <label for="newMonitor[V4LMultiBuffer]1">Yes</label>
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $newMonitor['V4LMultiBuffer'] == 0 ? 'checked="checked"' : '' ) ?>/>
        <label for="newMonitor[V4LMultiBuffer]0">No</label>
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( empty($newMonitor['V4LMultiBuffer']) ? 'checked="checked"' : '' ) ?>/>
        <label for="newMonitor[V4LMultiBuffer]">Use Config Value</label>
      </td></tr>
      <tr><td><?php echo translate('V4LCapturesPerFrame') ?></td><td><input type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo $newMonitor['V4LCapturesPerFrame'] ?>"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Remote" )
        {
?>
            <tr><td><?php echo translate('RemoteProtocol') ?></td><td><?php echo buildSelect( "newMonitor[Protocol]", $remoteProtocols, "updateMethods( this );if(this.value=='rtsp'){\$('RTSPDescribe').setStyle('display','table-row');}else{\$('RTSPDescribe').hide();}" ); ?></td></tr>
<?php
            if ( empty($newMonitor['Protocol']) || $newMonitor['Protocol'] == "http" )
            {
?>
            <tr><td><?php echo translate('RemoteMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $httpMethods ); ?></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?php echo translate('RemoteMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
<?php
            }
?>
            <tr><td><?php echo translate('RemoteHostName') ?></td><td><input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($newMonitor['Host']) ?>" size="36"/></td></tr>
            <tr><td><?php echo translate('RemoteHostPort') ?></td><td><input type="text" name="newMonitor[Port]" value="<?php echo validHtmlStr($newMonitor['Port']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('RemoteHostPath') ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "File" )
        {
?>
            <tr><td><?php echo translate('SourcePath') ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "cURL" )
        {
?>
            <tr><td><?php echo "URL" ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?php echo "Username" ?></td><td><input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($newMonitor['User']) ?>" size="12"/></td></tr>
            <tr><td><?php echo "Password" ?></td><td><input type="text" name="newMonitor[Pass]" value="<?php echo validHtmlStr($newMonitor['Pass']) ?>" size="12"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Ffmpeg" || $newMonitor['Type'] == "Libvlc")
        {
?>
      <tr><td><?php echo translate('SourcePath') ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?php echo translate('RemoteMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
      <tr><td><?php echo translate('Options') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_'.strtoupper($newMonitor['Type']), 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" name="newMonitor[Options]" value="<?php echo validHtmlStr($newMonitor['Options']) ?>" size="36"/></td></tr>
<?php
        }
?>
            <tr><td><?php echo translate('TargetColorspace') ?></td><td><select name="newMonitor[Colours]"><?php foreach ( $Colours as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Colours'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('CaptureWidth') ?> (<?php echo translate('Pixels') ?>)</td><td><input type="text" name="newMonitor[Width]" value="<?php echo validHtmlStr($newMonitor['Width']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?php echo translate('CaptureHeight') ?> (<?php echo translate('Pixels') ?>)</td><td><input type="text" name="newMonitor[Height]" value="<?php echo validHtmlStr($newMonitor['Height']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?php echo translate('PreserveAspect') ?></td><td><input type="checkbox" name="preserveAspectRatio" value="1"/></td></tr> 
            <tr><td><?php echo translate('Orientation') ?></td><td><select name="newMonitor[Orientation]"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Orientation'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
        if ( $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?php echo translate('Deinterlacing') ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts_v4l2 as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
        } else {
?>
            <tr><td><?php echo translate('Deinterlacing') ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
        }
?>
<?php
        if ( $newMonitor['Type'] == "Remote" )
        {
?>
            <tr id="RTSPDescribe"<?php if ( $newMonitor['Protocol'] != 'rtsp' ) { echo ' style="display:none;"'; } ?>><td><?php echo translate('RTSPDescribe') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_RTSPDESCRIBE', 'zmOptionHelp', 'optionhelp', '?' ) ?>) </td><td><input type="checkbox" name="newMonitor[RTSPDescribe]" value="1"<?php if ( !empty($newMonitor['RTSPDescribe']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        }
?>

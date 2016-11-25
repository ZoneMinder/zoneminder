<?php if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" ) { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('DevicePath') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Device]" value="<?php echo validHtmlStr($newMonitor['Device']) ?>"></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('CaptureMethod') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[Method]", $localMethods, "submitTab( '$tab' )", 'form-control' ); ?></div>
</div>
<?php if ( ZM_HAS_V4L1 && $newMonitor['Method'] == 'v4l1' ) { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('DeviceChannel') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Channel]"><?php foreach ( $v4l1DeviceChannels as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('DeviceFormat') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Format]"><?php foreach ( $v4l1DeviceFormats as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('CapturePalette') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Palette]"><?php foreach ( $v4l1LocalPalettes as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
<?php } else { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('DeviceChannel') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Channel]"><?php foreach ( $v4l2DeviceChannels as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('DeviceFormat') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Format]"><?php foreach ( $v4l2DeviceFormats as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('CapturePalette') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Palette]"><?php foreach ( $v4l2LocalPalettes as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
<?php } ?>

<div class="form-group">
	<label class="col-sm-3 control-label"><?php echo translate('V4LMultiBuffer') ?></label>
	<div class="col-sm-3">

<label class="radio-inline">
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $newMonitor['V4LMultiBuffer'] == 1 ? 'checked="checked"' : '' ) ?>/>
	Yes
</label>

<label class="radio-inline">
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $newMonitor['V4LMultiBuffer'] == 0 ? 'checked="checked"' : '' ) ?>/>
	No
</label>

<label class="radio-inline">
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( empty($newMonitor['V4LMultiBuffer']) ? 'checked="checked"' : '' ) ?>/>
        Use Config Value
</label>
	</div>

</div>

      <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('V4LCapturesPerFrame') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo $newMonitor['V4LCapturesPerFrame'] ?>"/></div>
</div>
<?php } elseif ( $newMonitor['Type'] == "Remote" ) { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('RemoteProtocol') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[Protocol]", $remoteProtocols, "updateMethods( this );if(this.value=='rtsp'){\$('RTSPDescribe').setStyle('display','table-row');}else{\$('RTSPDescribe').hide();}", 'form-control' ); ?></div>
</div>
<?php if ( empty($newMonitor['Protocol']) || $newMonitor['Protocol'] == "http" ) { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('RemoteMethod') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[Method]", $httpMethods, '', 'form-control' ); ?></div>
</div>
<?php } else { ?> <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('RemoteMethod') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[Method]", $rtspMethods, '', 'form-control' ); ?></div>
</div>
<?php } ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('RemoteHostName') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($newMonitor['Host']) ?>"></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('RemoteHostPort') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Port]" value="<?php echo validHtmlStr($newMonitor['Port']) ?>"></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('RemoteHostPath') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>"></div>
</div>
<?php } elseif ( $newMonitor['Type'] == "File" ) { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('SourcePath') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>"></div>
</div>
<?php } elseif ( $newMonitor['Type'] == "cURL" ) { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo "URL" ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>"></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo "Username" ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($newMonitor['User']) ?>"></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo "Password" ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Pass]" value="<?php echo validHtmlStr($newMonitor['Pass']) ?>"></div>
</div>
<?php } elseif ( $newMonitor['Type'] == "Ffmpeg" || $newMonitor['Type'] == "Libvlc") { ?>
      <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('SourcePath') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>"></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('RemoteMethod') ?></label><div class="col-sm-3"><?php echo buildSelect( "newMonitor[Method]", $rtspMethods, '', 'form-control' ); ?></div>
</div>
      <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Options') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_'.strtoupper($newMonitor['Type']), 'zmOptionHelp', 'optionhelp', '?' ) ?>)</label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[Options]" value="<?php echo validHtmlStr($newMonitor['Options']) ?>"></div>
</div>
<?php } ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('TargetColorspace') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Colours]"><?php foreach ( $Colours as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Colours'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('CaptureWidth') ?> (<?php echo translate('Pixels') ?>)</label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($newMonitor['Width']) ?>"></div>
</div>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('CaptureHeight') ?> (<?php echo translate('Pixels') ?>)</label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($newMonitor['Height']) ?>"></div>
</div>

<div class="form-group">
<div class="col-sm-3 col-sm-offset-3">
<div class="checkbox">
<label>
<input type="checkbox" name="preserveAspectRatio" value="1"/>
<?php echo translate('PreserveAspect') ?>
</label>
</div> 
</div>
</div>

            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Orientation') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Orientation]"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Orientation'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
<?php if ( $newMonitor['Type'] == "Local" ) { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Deinterlacing') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts_v4l2 as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
<?php } else { ?>
            <div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('Deinterlacing') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
<?php } if ( $newMonitor['Type'] == "Remote" ) { ?>
            <tr id="RTSPDescribe"<?php if ( $newMonitor['Protocol'] != 'rtsp' ) { echo ' style="display:none;"'; } ?>><label class="col-sm-3 control-label"><?php echo translate('RTSPDescribe') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_RTSPDESCRIBE', 'zmOptionHelp', 'optionhelp', '?' ) ?>) </label><div class="col-sm-3"><input class="form-control" type="checkbox" name="newMonitor[RTSPDescribe]" value="1"<?php if ( !empty($newMonitor['RTSPDescribe']) ) { ?> checked="checked"<?php } ?>/></div>
</div>
<?php } ?>

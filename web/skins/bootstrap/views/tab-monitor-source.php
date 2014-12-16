<div role="tabpanel" class="form-horizontal tab-pane" id="source">

<?php
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
        }
        elseif ( $newMonitor['Type'] == "Remote" )
        {
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

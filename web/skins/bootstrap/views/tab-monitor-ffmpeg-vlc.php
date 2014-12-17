
			<tr><td><?= $SLANG['SourcePath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?= $SLANG['RemoteMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
			<tr><td><?= $SLANG['Options'] ?>&nbsp;(<?= makePopupLink( '?view=optionhelp&amp;option=OPTIONS_'.strtoupper($newMonitor['Type']), 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" name="newMonitor[Options]" value="<?= validHtmlStr($newMonitor['Options']) ?>" size="36"/></td></tr>

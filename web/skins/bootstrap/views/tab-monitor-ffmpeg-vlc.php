<div ng-show="monitor.sourceType == 'Libvlc' || sourceType == 'Ffmpeg'">
	<div class="form-group">
		<label><?= $SLANG['SourcePath'] ?></label>
		<input class="form-control" type="text" ng-model="monitor.Path" />
	</div>

	<div class="form-group">
		<label><?= $SLANG['RemoteMethod'] ?></label>
		<?= buildSelect( "newMonitor[Method]", $rtspMethods ); ?>
	</div>


	<div class="form-group">
		<label><?= $SLANG['Options'] ?>&nbsp;(<?= makePopupLink( '?view=optionhelp&amp;option=OPTIONS_'.strtoupper($newMonitor['Type']), 'zmOptionHelp', 'optionhelp', '?' ) ?>)</label>
		<input class="form-control" type="text" ng-model="monitor.Options" />
	</div>
</div>

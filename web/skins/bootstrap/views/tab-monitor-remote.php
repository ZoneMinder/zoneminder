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

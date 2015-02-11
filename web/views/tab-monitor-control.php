<div role="tabpanel" class="form-horizontal tab-pane" id="control">


            <tr><td><?= $SLANG['Controllable'] ?></td><td><input type="checkbox" name="newMonitor[Controllable]" value="1"<?php if ( !empty($newMonitor['Controllable']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><td><?= $SLANG['ControlType'] ?></td><td><?= buildSelect( "newMonitor[ControlId]", $controlTypes, 'loadLocations( this )' ); ?><?php if ( canEdit( 'Control' ) ) { ?>&nbsp;<a href="#" onclick="createPopup( '?view=controlcaps', 'zmControlCaps', 'controlcaps' );"><?= $SLANG['Edit'] ?></a><?php } ?></td></tr>
            <tr><td><?= $SLANG['ControlDevice'] ?></td><td><input type="text" name="newMonitor[ControlDevice]" value="<?= validHtmlStr($newMonitor['ControlDevice']) ?>" size="32"/></td></tr>
            <tr><td><?= $SLANG['ControlAddress'] ?></td><td><input type="text" name="newMonitor[ControlAddress]" value="<?= validHtmlStr($newMonitor['ControlAddress']) ?>" size="32"/></td></tr>
            <tr><td><?= $SLANG['AutoStopTimeout'] ?></td><td><input type="text" name="newMonitor[AutoStopTimeout]" value="<?= validHtmlStr($newMonitor['AutoStopTimeout']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['TrackMotion'] ?></td><td><input type="checkbox" name="newMonitor[TrackMotion]" value="1"<?php if ( !empty($newMonitor['TrackMotion']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        $return_options = array(
            '-1' => $SLANG['None'],
            '0' => $SLANG['Home'],
            '1' => $SLANG['Preset']." 1",
        );
?>
            <tr><td><?= $SLANG['TrackDelay'] ?></td><td><input type="text" name="newMonitor[TrackDelay]" value="<?= validHtmlStr($newMonitor['TrackDelay']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['ReturnLocation'] ?></td><td><?= buildSelect( "newMonitor[ReturnLocation]", $return_options ); ?></td></tr>
            <tr><td><?= $SLANG['ReturnDelay'] ?></td><td><input type="text" name="newMonitor[ReturnDelay]" value="<?= validHtmlStr($newMonitor['ReturnDelay']) ?>" size="4"/></td></tr>

</div>

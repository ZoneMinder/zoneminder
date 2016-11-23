            <tr><td><?php echo translate('Controllable') ?></td><td><input type="checkbox" name="newMonitor[Controllable]" value="1"<?php if ( !empty($newMonitor['Controllable']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><td><?php echo translate('ControlType') ?></td><td><?php echo buildSelect( "newMonitor[ControlId]", $controlTypes, 'loadLocations( this )' ); ?><?php if ( canEdit( 'Control' ) ) { ?>&nbsp;<a href="#" onclick="createPopup( '?view=controlcaps', 'zmControlCaps', 'controlcaps' );"><?php echo translate('Edit') ?></a><?php } ?></td></tr>
            <tr><td><?php echo translate('ControlDevice') ?></td><td><input type="text" name="newMonitor[ControlDevice]" value="<?php echo validHtmlStr($newMonitor['ControlDevice']) ?>" size="32"/></td></tr>
            <tr><td><?php echo translate('ControlAddress') ?></td><td><input type="text" name="newMonitor[ControlAddress]" value="<?php echo validHtmlStr($newMonitor['ControlAddress']) ?>" size="32"/></td></tr>
            <tr><td><?php echo translate('AutoStopTimeout') ?></td><td><input type="text" name="newMonitor[AutoStopTimeout]" value="<?php echo validHtmlStr($newMonitor['AutoStopTimeout']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('TrackMotion') ?></td><td><input type="checkbox" name="newMonitor[TrackMotion]" value="1"<?php if ( !empty($newMonitor['TrackMotion']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        $return_options = array(
            '-1' => translate('None'),
            '0' => translate('Home'),
            '1' => translate('Preset')." 1",
        );
?>
            <tr><td><?php echo translate('TrackDelay') ?></td><td><input type="text" name="newMonitor[TrackDelay]" value="<?php echo validHtmlStr($newMonitor['TrackDelay']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('ReturnLocation') ?></td><td><?php echo buildSelect( "newMonitor[ReturnLocation]", $return_options ); ?></td></tr>
            <tr><td><?php echo translate('ReturnDelay') ?></td><td><input type="text" name="newMonitor[ReturnDelay]" value="<?php echo validHtmlStr($newMonitor['ReturnDelay']) ?>" size="4"/></td></tr>

            <tr><td><?php echo translate('EventPrefix') ?></td><td><input type="text" name="newMonitor[EventPrefix]" value="<?php echo validHtmlStr($newMonitor['EventPrefix']) ?>" size="24"/></td></tr>
            <tr><td><?php echo translate('Sectionlength') ?></td><td><input type="text" name="newMonitor[SectionLength]" value="<?php echo validHtmlStr($newMonitor['SectionLength']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('FrameSkip') ?></td><td><input type="text" name="newMonitor[FrameSkip]" value="<?php echo validHtmlStr($newMonitor['FrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('MotionFrameSkip') ?></td><td><input type="text" name="newMonitor[MotionFrameSkip]" value="<?php echo validHtmlStr($newMonitor['MotionFrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('AnalysisUpdateDelay') ?></td><td><input type="text" name="newMonitor[AnalysisUpdateDelay]" value="<?php echo validHtmlStr($newMonitor['AnalysisUpdateDelay']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('FPSReportInterval') ?></td><td><input type="text" name="newMonitor[FPSReportInterval]" value="<?php echo validHtmlStr($newMonitor['FPSReportInterval']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('DefaultView') ?></td><td><select name="newMonitor[DefaultView]">
<?php
        foreach ( getEnumValues( 'Monitors', 'DefaultView' ) as $opt_view )
        {
          if ( $opt_view == 'Control' && ( !ZM_OPT_CONTROL || !$monitor['Controllable'] ) )
            continue;
?>
              <option value="<?php echo $opt_view ?>"<?php if ( $opt_view == $newMonitor['DefaultView'] ) { ?> selected="selected"<?php } ?>><?php echo $opt_view ?></option>
<?php
        }
?>
            </select></td></tr>
            <tr><td><?php echo translate('DefaultRate') ?></td><td><?php echo buildSelect( "newMonitor[DefaultRate]", $rates ); ?></td></tr>
            <tr><td><?php echo translate('DefaultScale') ?></td><td><?php echo buildSelect( "newMonitor[DefaultScale]", $scales ); ?></td></tr>
<?php
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?php echo translate('SignalCheckColour') ?></td><td><input type="text" name="newMonitor[SignalCheckColour]" value="<?php echo validHtmlStr($newMonitor['SignalCheckColour']) ?>" size="10" onchange="$('SignalCheckSwatch').setStyle( 'backgroundColor', this.value )"/><span id="SignalCheckSwatch" class="swatch" style="background-color: <?php echo $newMonitor['SignalCheckColour'] ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>
<?php
        }
?>
            <tr><td><?php echo translate('WebColour') ?></td><td><input type="text" name="newMonitor[WebColour]" value="<?php echo validHtmlStr($newMonitor['WebColour']) ?>" size="10" onchange="$('WebSwatch').setStyle( 'backgroundColor', this.value )"/><span id="WebSwatch" class="swatch" style="background-color: <?php echo validHtmlStr($newMonitor['WebColour']) ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>
            <tr><td><?php echo translate('Exif') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_EXIF', 'zmOptionHelp', 'optionhelp', '?' ) ?>) </td><td><input type="checkbox" name="newMonitor[Exif]" value="1"<?php if ( !empty($newMonitor['Exif']) ) { ?> checked="checked"<?php } ?>/></td></tr>

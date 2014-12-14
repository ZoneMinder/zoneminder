<div role="tabpanel" class="form-horizontal tab-pane" id="misc">


            <tr><td><?= $SLANG['EventPrefix'] ?></td><td><input type="text" name="newMonitor[EventPrefix]" value="<?= validHtmlStr($newMonitor['EventPrefix']) ?>" size="24"/></td></tr>
            <tr><td><?= $SLANG['Sectionlength'] ?></td><td><input type="text" name="newMonitor[SectionLength]" value="<?= validHtmlStr($newMonitor['SectionLength']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['FrameSkip'] ?></td><td><input type="text" name="newMonitor[FrameSkip]" value="<?= validHtmlStr($newMonitor['FrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['MotionFrameSkip'] ?></td><td><input type="text" name="newMonitor[MotionFrameSkip]" value="<?= validHtmlStr($newMonitor['MotionFrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['FPSReportInterval'] ?></td><td><input type="text" name="newMonitor[FPSReportInterval]" value="<?= validHtmlStr($newMonitor['FPSReportInterval']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['DefaultView'] ?></td><td><select name="newMonitor[DefaultView]">
<?php
        foreach ( getEnumValues( 'Monitors', 'DefaultView' ) as $opt_view )
        {
          if ( $opt_view == 'Control' && ( !ZM_OPT_CONTROL || !$monitor['Controllable'] ) )
            continue;
?>
              <option value="<?= $opt_view ?>"<?php if ( $opt_view == $newMonitor['DefaultView'] ) { ?> selected="selected"<?php } ?>><?= $opt_view ?></option>
<?php
        }
?>
            </select></td></tr>
            <tr><td><?= $SLANG['DefaultRate'] ?></td><td><?= buildSelect( "newMonitor[DefaultRate]", $rates ); ?></td></tr>
            <tr><td><?= $SLANG['DefaultScale'] ?></td><td><?= buildSelect( "newMonitor[DefaultScale]", $scales ); ?></td></tr>
<?php
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?= $SLANG['SignalCheckColour'] ?></td><td><input type="text" name="newMonitor[SignalCheckColour]" value="<?= validHtmlStr($newMonitor['SignalCheckColour']) ?>" size="10" onchange="$('SignalCheckSwatch').setStyle( 'backgroundColor', this.value )"/><span id="SignalCheckSwatch" class="swatch" style="background-color: <?= $newMonitor['SignalCheckColour'] ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>
<?php
        }
?>
            <tr><td><?= $SLANG['WebColour'] ?></td><td><input type="text" name="newMonitor[WebColour]" value="<?= validHtmlStr($newMonitor['WebColour']) ?>" size="10" onchange="$('WebSwatch').setStyle( 'backgroundColor', this.value )"/><span id="WebSwatch" class="swatch" style="background-color: <?= validHtmlStr($newMonitor['WebColour']) ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>



            <tr>
              <td><?= $SLANG['LinkedMonitors'] ?></td>
              <td>
                <select name="monitorIds" size="4" multiple="multiple" onchange="updateLinkedMonitors( this )">
<?php
    $monitors = dbFetchAll( "select Id,Name from Monitors order by Sequence asc" );
    if ( !empty($newMonitor['LinkedMonitors']) )
        $monitorIds = array_flip( explode( ',', $newMonitor['LinkedMonitors'] ) );
    else
        $monitorIds = array();
    foreach ( $monitors as $monitor )
    {
        if ( (empty($newMonitor['Id']) || ($monitor['Id'] != $newMonitor['Id'])) && visibleMonitor( $monitor['Id'] ) )
        {
?>
                  <option value="<?= $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?= validHtmlStr($monitor['Name']) ?></option>
<?php
        }
    }
?>
                </select>
              </td>
            </tr>
            <tr><td><?= $SLANG['Triggers'] ?></td><td>
<?php
        $optTriggers = getSetValues( 'Monitors', 'Triggers' );
        $breakCount = (int)(ceil(count($optTriggers)));
        $breakCount = min( 3, $breakCount );
        $optCount = 0;
        foreach( $optTriggers as $optTrigger )
        {
            if ( !ZM_OPT_X10 && $optTrigger == 'X10' )
                continue;
            if ( $optCount && ($optCount%$breakCount == 0) )
                echo "</br>";
?>
              <input type="checkbox" name="newMonitor[Triggers][]" value="<?= $optTrigger ?>"<?php if ( isset($newMonitor['Triggers']) && in_array( $optTrigger, $newMonitor['Triggers'] ) ) { ?> checked="checked"<?php } ?>/>&nbsp;<?= $optTrigger ?>
<?php
            $optCount ++;
        }
        if ( !$optCount )
        {
?>
              <em><?= $SLANG['NoneAvailable'] ?></em>
<?php
        }
?>
            </td></tr>

</div>

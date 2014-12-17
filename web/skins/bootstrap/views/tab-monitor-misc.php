<div role="tabpanel" class="tab-pane" id="misc">


            <div class="form-group"><label><?= $SLANG['EventPrefix'] ?></label><input class="form-control" type="text" ng-model="monitor.EventPrefix" /></div>
            <div class="form-group"><label><?= $SLANG['Sectionlength'] ?></label><input class="form-control" type="text" ng-model="monitor.SectionLength" /></div>
            <div class="form-group"><label><?= $SLANG['FrameSkip'] ?></label><input class="form-control" type="text" ng-model="monitor.FrameSkip" /></div>
            <div class="form-group"><label><?= $SLANG['MotionFrameSkip'] ?></label><input class="form-control" type="text" ng-model="monitor.MotionFrameSkip" /></div>
            <div class="form-group"><label><?= $SLANG['FPSReportInterval'] ?></label><input class="form-control" type="text" ng-model="monitor.FPSReportInterval" /></div>
            <div class="form-group"><label><?= $SLANG['DefaultView'] ?></label><select class="form-control" ng-model="monitor.DefaultView">
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
            </select></div>
            <div class="form-group"><label><?= $SLANG['DefaultRate'] ?></label><?= buildSelect( "newMonitor[DefaultRate", $rates ); ?></div>
            <div class="form-group"><label><?= $SLANG['DefaultScale'] ?></label><?= buildSelect( "newMonitor[DefaultScale", $scales ); ?></div>
            <div ng-show="monitor.sourceType == 'Local'" class="form-group"><label><?= $SLANG['SignalCheckColour'] ?></label><input class="form-control" type="text" ng-model="monitor.SignalCheckColour" onchange="$('SignalCheckSwatch').setStyle( 'backgroundColor', this.value )"/><span id="SignalCheckSwatch" class="swatch" style="background-color: <?= $newMonitor['SignalCheckColour'] ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
	<div class="form-group">
		<label><?= $SLANG['WebColour'] ?></label>
		<input class="form-control" type="text" ng-model="monitor.WebColour" style="color: {{ monitor.WebColour}};" required />
	</div>



            <div class="form-group">
              <label><?= $SLANG['LinkedMonitors'] ?></label>
                <select class="form-control" ng-model="monitor.monitorIds" multiple="multiple" onchange="updateLinkedMonitors( this )">
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
              </div>
            <div class="form-group"><label><?= $SLANG['Triggers'] ?></label>
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
              <input class="form-control" type="checkbox" ng-model="monitor.Triggers" value="<?= $optTrigger ?>"<?php if ( isset($newMonitor['Triggers']) && in_array( $optTrigger, $newMonitor['Triggers'] ) ) { ?> checked="checked"<?php } ?>/>&nbsp;<?= $optTrigger ?>
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
            </div>

</div>

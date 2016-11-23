            <tr><td><?php echo translate('Name') ?></td><td><input type="text" name="newMonitor[Name]" value="<?php echo validHtmlStr($newMonitor['Name']) ?>" size="16"/></td></tr>
            <tr><td><?php echo translate('Server') ?></td><td>
<?php 
  $servers = array(''=>'None');
  $result = dbQuery( 'SELECT * FROM Servers ORDER BY Name');
  $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Server' );
  foreach ( $results as $row => $server_obj ) {
    $servers[$server_obj->Id] = $server_obj->Name();
  }
?>
  <?php echo buildSelect( "newMonitor[ServerId]", $servers ); ?>
</td></tr>
            <tr><td><?php echo translate('SourceType') ?></td><td><?php echo buildSelect( "newMonitor[Type]", $sourceTypes ); ?></td></tr>
            <tr><td><?php echo translate('Function') ?></td><td><select name="newMonitor[Function]">
<?php
        foreach ( getEnumValues( 'Monitors', 'Function' ) as $optFunction )
        {
?>
              <option value="<?php echo $optFunction ?>"<?php if ( $optFunction == $newMonitor['Function'] ) { ?> selected="selected"<?php } ?>><?php echo translate('Fn'.$optFunction) ?></option>
<?php
        }
?>
            </select></td></tr>
            <tr><td><?php echo translate('Enabled') ?></td><td><input type="checkbox" name="newMonitor[Enabled]" value="1"<?php if ( !empty($newMonitor['Enabled']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr>
              <td><?php echo translate('LinkedMonitors') ?></td>
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
                  <option value="<?php echo $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?php echo validHtmlStr($monitor['Name']) ?></option>
<?php
        }
    }
?>
                </select>
              </td>
            </tr>
            <tr><td><?php echo translate('AnalysisFPS') ?></td><td><input type="text" name="newMonitor[AnalysisFPS]" value="<?php echo validHtmlStr($newMonitor['AnalysisFPS']) ?>" size="6"/></td></tr>
<?php
    if ( $newMonitor['Type'] != "Local" && $newMonitor['Type'] != "File" )
    {
?>
            <tr><td><?php echo translate('MaximumFPS') ?>&nbsp;(<?php echo makePopupLink('?view=optionhelp&amp;option=OPTIONS_MAXFPS', 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" onclick="document.getElementById('newMonitor[MaxFPS]').innerHTML= ' CAUTION: See the help text'" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($newMonitor['MaxFPS']) ?>" size="5"/><span id="newMonitor[MaxFPS]" style="color:red"></span></td></tr>
            <tr><td><?php echo translate('AlarmMaximumFPS') ?>&nbsp;(<?php echo makePopupLink('?view=optionhelp&amp;option=OPTIONS_MAXFPS', 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" onclick="document.getElementById('newMonitor[AlarmMaxFPS]').innerHTML= ' CAUTION: See the help text'" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" size="5"/><span id="newMonitor[AlarmMaxFPS]" style="color:red"></span></td></tr>
<?php
    } else {
?>
            <tr><td><?php echo translate('MaximumFPS') ?></td><td><input type="text" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($newMonitor['MaxFPS']) ?>" size="5"/></td></tr>
            <tr><td><?php echo translate('AlarmMaximumFPS') ?></td><td><input type="text" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" size="5"/></td></tr>
<?php
    }
  if ( ZM_FAST_IMAGE_BLENDS )
        {
?>
            <tr><td><?php echo translate('RefImageBlendPct') ?></td><td><select name="newMonitor[RefBlendPerc]"><?php foreach ( $fastblendopts as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['RefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('AlmRefImageBlendPct') ?></td><td><select name="newMonitor[AlarmRefBlendPerc]"><?php foreach ( $fastblendopts_alarm as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['AlarmRefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
  } else {
?>
            <tr><td><?php echo translate('RefImageBlendPct') ?></td><td><input type="text" name="newMonitor[RefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['RefBlendPerc']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('AlarmRefImageBlendPct') ?></td><td><input type="text" name="newMonitor[AlarmRefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['AlarmRefBlendPerc']) ?>" size="4"/></td></tr>
<?php
        }
?>
            <tr><td><?php echo translate('Triggers') ?></td><td>
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
              <input type="checkbox" name="newMonitor[Triggers][]" value="<?php echo $optTrigger ?>"<?php if ( isset($newMonitor['Triggers']) && in_array( $optTrigger, $newMonitor['Triggers'] ) ) { ?> checked="checked"<?php } ?>/>&nbsp;<?php echo $optTrigger ?>
<?php
            $optCount ++;
        }
        if ( !$optCount )
        {
?>
              <em><?php echo translate('NoneAvailable') ?></em>
<?php
        }
?>
            </td></tr>

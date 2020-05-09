var optControl = <?php echo ZM_OPT_CONTROL ?>;
var defaultAspectRatio = '<?php echo ZM_DEFAULT_ASPECT_RATIO ?>';

<?php
if ( ZM_OPT_CONTROL ) {
?>
var controlOptions = new Object();
<?php
  global $controlTypes;
  $controlTypes = array( ''=>translate('None') );
  # Temporary workaround to show all ptz control types regardless of monitor source type
  #    $sql = "select * from Controls where Type = '".$newMonitor['Type']."'";
  $sql = 'SELECT `Id`,`Name`,`HasHomePreset`,`NumPresets` FROM `Controls` ORDER BY lower(`Name`)';
  foreach( dbFetchAll($sql) as $row ) {
    $controlTypes[$row['Id']] = $row['Name'];
    echo '
controlOptions['.$row['Id'].'] = new Array();
controlOptions['.$row['Id'].'][0] = '.
    ( $row['HasHomePreset'] ? '\''.translate('Home').'\'' : 'null' ).'
';
    for ( $i = 1; $i <= $row['NumPresets']; $i++ ) {
      echo 'controlOptions['. $row['Id'].']['.$i.'] = \''.translate('Preset').' '.$i .'\';
';
    }
  } # end foreach row
} # end if ZM_OPT_CONTROL
?>

var monitorNames = new Object();
<?php
$query = empty($_REQUEST['mid']) ? dbQuery('SELECT Name FROM Monitors') : dbQuery('SELECT Name FROM Monitors WHERE Id != ?', array($_REQUEST['mid']) );
if ( $query ) {
  while ( $name = dbFetchNext($query, 'Name') ) {
    echo '
monitorNames[\''.validJsStr($name).'\'] = true;
';
  } // end foreach
} # end if query
?>

function validateForm( form ) {
  var errors = new Array();

  if ( form.elements['newMonitor[Name]'].value.search( /[^\w\-\.\(\)\:\/ ]/ ) >= 0 )
    errors[errors.length] = "<?php echo translate('BadNameChars') ?>";
  else if ( monitorNames[form.elements['newMonitor[Name]'].value] )
    errors[errors.length] = "<?php echo translate('DuplicateMonitorName') ?>";

  if ( form.elements['newMonitor[Type]'].value == 'Local' ) {
    if ( !form.elements['newMonitor[Palette]'].value || !form.elements['newMonitor[Palette]'].value.match( /^\d+$/ ) )
      errors[errors.length] = "<?php echo translate('BadPalette') ?>";
    if ( !form.elements['newMonitor[Device]'].value )
      errors[errors.length] = "<?php echo translate('BadDevice') ?>";
    if ( !form.elements['newMonitor[Channel]'].value || !form.elements['newMonitor[Channel]'].value.match( /^\d+$/ ) )
      errors[errors.length] = "<?php echo translate('BadChannel') ?>";
    if ( !form.elements['newMonitor[Format]'].value || !form.elements['newMonitor[Format]'].value.match( /^\d+$/ ) )
      errors[errors.length] = "<?php echo translate('BadFormat') ?>";
  } else if ( form.elements['newMonitor[Type]'].value == 'Remote' ) {
    //if ( !form.elements['newMonitor[Host]'].value || !form.elements['newMonitor[Host]'].value.match( /^[0-9a-zA-Z_.:@-]+$/ ) )
      //errors[errors.length] = "<?php echo translate('BadHost') ?>";
    if ( form.elements['newMonitor[Port]'].value && !form.elements['newMonitor[Port]'].value.match( /^\d+$/ ) )
      errors[errors.length] = "<?php echo translate('BadPort') ?>";
    //if ( !form.elements['newMonitor[Path]'].value )
      //errors[errors.length] = "<?php echo translate('BadPath') ?>";
  } else if ( form.elements['newMonitor[Type]'].value == 'Ffmpeg' ) {
    if ( !form.elements['newMonitor[Path]'].value )
//|| !form.elements['newMonitor[Path]'].value.match( /^\d+$/ ) ) // valid url
      errors[errors.length] = "<?php echo translate('BadPath') ?>";

  } else if ( form.elements['newMonitor[Type]'].value == 'File' ) {
    if ( !form.elements['newMonitor[Path]'].value )
      errors[errors.length] = "<?php echo translate('BadPath') ?>";
  } else if ( form.elements['newMonitor[Type]'].value == 'WebSite' ) {
    if ( form.elements['newMonitor[Function]'].value != 'Monitor' && form.elements['newMonitor[Function]'].value != 'None')
      errors[errors.length] = "<?php echo translate('BadSourceType') ?>";
    if ( form.elements['newMonitor[Path]'].value.search(/^https?:\/\//i) )
      errors[errors.length] = "<?php echo translate('BadWebSitePath') ?>";
  }

  if ( form.elements['newMonitor[Type]'].value != 'WebSite' ) {

    if ( form.elements['newMonitor[AnalysisFPSLimit]'].value && !(parseFloat(form.elements['newMonitor[AnalysisFPSLimit]'].value) > 0 ) )
      errors[errors.length] = "<?php echo translate('BadAnalysisFPS') ?>";
    if ( form.elements['newMonitor[MaxFPS]'].value && !(parseFloat(form.elements['newMonitor[MaxFPS]'].value) > 0 ) )
      errors[errors.length] = "<?php echo translate('BadMaxFPS') ?>";
    if ( form.elements['newMonitor[AlarmMaxFPS]'].value && !(parseFloat(form.elements['newMonitor[AlarmMaxFPS]'].value) > 0 ) )
      errors[errors.length] = "<?php echo translate('BadAlarmMaxFPS') ?>";
    if ( !form.elements['newMonitor[RefBlendPerc]'].value || (parseInt(form.elements['newMonitor[RefBlendPerc]'].value) > 100 ) || (parseInt(form.elements['newMonitor[RefBlendPerc]'].value) < 0 ) )
      errors[errors.length] = "<?php echo translate('BadRefBlendPerc') ?>";

    if ( !form.elements['newMonitor[Colours]'].value || (parseInt(form.elements['newMonitor[Colours]'].value) != 1 && parseInt(form.elements['newMonitor[Colours]'].value) != 3 && parseInt(form.elements['newMonitor[Colours]'].value) != 4 ) )
      errors[errors.length] = "<?php echo translate('BadColours') ?>";
    if ( !form.elements['newMonitor[Width]'].value || !(parseInt(form.elements['newMonitor[Width]'].value) > 0 ) )
      errors[errors.length] = "<?php echo translate('BadWidth') ?>";
    if ( !form.elements['newMonitor[Height]'].value || !(parseInt(form.elements['newMonitor[Height]'].value) > 0 ) )
      errors[errors.length] = "<?php echo translate('BadHeight') ?>";
    if ( !form.elements['newMonitor[LabelX]'].value || !(parseInt(form.elements['newMonitor[LabelX]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadLabelX') ?>";
    if ( !form.elements['newMonitor[LabelY]'].value || !(parseInt(form.elements['newMonitor[LabelY]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadLabelY') ?>";
    if ( !form.elements['newMonitor[ImageBufferCount]'].value || !(parseInt(form.elements['newMonitor[ImageBufferCount]'].value) >= 10 ) )
      errors[errors.length] = "<?php echo translate('BadImageBufferCount') ?>";
    if ( !form.elements['newMonitor[WarmupCount]'].value || !(parseInt(form.elements['newMonitor[WarmupCount]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadWarmupCount') ?>";
    if ( !form.elements['newMonitor[PreEventCount]'].value || !(parseInt(form.elements['newMonitor[PreEventCount]'].value) >= 0 ) || (parseInt(form.elements['newMonitor[PreEventCount]'].value) > parseInt(form.elements['newMonitor[ImageBufferCount]'].value)) )
      errors[errors.length] = "<?php echo translate('BadPreEventCount') ?>";
    if ( !form.elements['newMonitor[PostEventCount]'].value || !(parseInt(form.elements['newMonitor[PostEventCount]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadPostEventCount') ?>";
    if ( !form.elements['newMonitor[StreamReplayBuffer]'].value || !(parseInt(form.elements['newMonitor[StreamReplayBuffer]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadStreamReplayBuffer') ?>";
    if ( !form.elements['newMonitor[AlarmFrameCount]'].value || !(parseInt(form.elements['newMonitor[AlarmFrameCount]'].value) > 0 ) )
      errors[errors.length] = "<?php echo translate('BadAlarmFrameCount') ?>";
    if ( !form.elements['newMonitor[SectionLength]'].value || !(parseInt(form.elements['newMonitor[SectionLength]'].value) >= 30 ) )
      errors[errors.length] = "<?php echo translate('BadSectionLength') ?>";
    if ( !form.elements['newMonitor[AnalysisUpdateDelay]'].value || !(parseInt(form.elements['newMonitor[AnalysisUpdateDelay]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadAnalysisUpdateDelay') ?>";
    if ( !form.elements['newMonitor[FPSReportInterval]'].value || !(parseInt(form.elements['newMonitor[FPSReportInterval]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadFPSReportInterval') ?>";
    if ( !form.elements['newMonitor[FrameSkip]'].value || !(parseInt(form.elements['newMonitor[FrameSkip]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadFrameSkip') ?>";
    if ( !form.elements['newMonitor[MotionFrameSkip]'].value || !(parseInt(form.elements['newMonitor[MotionFrameSkip]'].value) >= 0 ) )
      errors[errors.length] = "<?php echo translate('BadMotionFrameSkip') ?>";
    if ( form.elements['newMonitor[Type]'].value == 'Local' )
      if ( !form.elements['newMonitor[SignalCheckColour]'].value || !form.elements['newMonitor[SignalCheckColour]'].value.match( /^[#0-9a-zA-Z]+$/ ) )
        errors[errors.length] = "<?php echo translate('BadSignalCheckColour') ?>";
    if ( !form.elements['newMonitor[WebColour]'].value || !form.elements['newMonitor[WebColour]'].value.match( /^[#0-9a-zA-Z]+$/ ) )
      errors[errors.length] = "<?php echo translate('BadWebColour') ?>";

  }

  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }

  var warnings = new Array();
  if ( (form.elements['newMonitor[Function]'].value != 'Monitor') && (form.elements['newMonitor[Function]'].value != 'None') ) {
    if ( (form.elements['newMonitor[SaveJPEGs]'].value == '0') && (form.elements['newMonitor[VideoWriter]'].value == '0') ) {
      warnings[warnings.length] = "<?php echo translate('BadNoSaveJPEGsOrVideoWriter'); ?>";
    }
  }
  if ( warnings.length ) {
    if ( !confirm(warnings.join("\n")) ) {
      return false;
    }
  }

  return true;
}

function updateMethods(element) {
  var form = element.form;

  var origMethod = form.elements['origMethod'];
  var methodSelector = form.elements['newMonitor[Method]'];
  methodSelector.options.length = 0;
  switch ( element.value ) {
    case 'http' :
      <?php
        foreach( $httpMethods as $value=>$label ) {
          ?>
            methodSelector.options[methodSelector.options.length] = new Option("<?php echo htmlspecialchars($label) ?>", "<?php echo $value ?>");
          if ( origMethod.value == "<?php echo $value ?>" )
            methodSelector.selectedIndex = methodSelector.options.length-1;
          <?php
        }
      ?>
          break;
    case 'rtsp' :
      <?php
        foreach( $rtspMethods as $value=>$label ) {
          ?>
            methodSelector.options[methodSelector.options.length] = new Option( "<?php echo htmlspecialchars($label) ?>", "<?php echo $value ?>" );
          if ( origMethod.value == "<?php echo $value ?>" )
            methodSelector.selectedIndex = form.elements['newMonitor[Method]'].options.length-1;
          <?php
        }
      ?>
    break;
  }
  return true;
}

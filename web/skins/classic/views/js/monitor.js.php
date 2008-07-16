var optControl = <?= ZM_OPT_CONTROL ?>;
var defaultAspectRatio = '<?= ZM_DEFAULT_ASPECT_RATIO ?>';

<?php
if ( ZM_OPT_CONTROL )
{
?>
var controlOptions = new Object();
<?php
    global $controlTypes;
    $controlTypes = array( ''=>$SLANG['None'] );
    $sql = "select * from Controls where Type = '".$monitor['Type']."'";
    foreach( dbFetchAll( $sql ) as $row )
    {
        $controlTypes[$row['Id']] = $row['Name'];
?>
controlOptions[<?= $row['Id'] ?>] = new Array();
<?php
        if ( $row['HasHomePreset'] )
        {
?>
controlOptions[<?= $row['Id'] ?>][0] = '<?= $SLANG['Home'] ?>';
<?php
        }
        else
        {
?>
controlOptions[<?= $row['Id'] ?>][0] = null;
<?php
        }
        for ( $i = 1; $i <= $row['NumPresets']; $i++ )
        {
?>
controlOptions[<?= $row['Id'] ?>][<?= $i ?>] = '<?= $SLANG['Preset'].' '.$i ?>';
<?php
        }
    }
}
?>

function validateForm( form )
{
    var errors = new Array();

    if ( form.elements['newMonitor[Name]'].value.search( /[^\w-]/ ) >= 0 )
        errors[errors.length] = "<?= $SLANG['BadNameChars'] ?>";
    if ( form.elements['newMonitor[MaxFPS]'].value && !(parseFloat(form.elements['newMonitor[MaxFPS]'].value) > 0 ) )
        errors[errors.length] = "<?= $SLANG['BadMaxFPS'] ?>";
    if ( form.elements['newMonitor[AlarmMaxFPS]'].value && !(parseFloat(form.elements['newMonitor[AlarmMaxFPS]'].value) > 0 ) )
        errors[errors.length] = "<?= $SLANG['BadAlarmMaxFPS'] ?>";
    if ( !form.elements['newMonitor[RefBlendPerc]'].value || !(parseInt(form.elements['newMonitor[RefBlendPerc]'].value) > 0 ) )
        errors[errors.length] = "<?= $SLANG['BadRefBlendPerc'] ?>";
    if ( form.elements['newMonitor[Type]'].value == 'Local' )
    {
        if ( !form.elements['newMonitor[Device]'].value )
            errors[errors.length] = "<?= $SLANG['BadDevice'] ?>";
        if ( !form.elements['newMonitor[Channel]'].value || !form.elements['newMonitor[Channel]'].value.match( /^\d+$/ ) )
            errors[errors.length] = "<?= $SLANG['BadChannel'] ?>";
        if ( !form.elements['newMonitor[Format]'].value || !form.elements['newMonitor[Format]'].value.match( /^\d+$/ ) )
            errors[errors.length] = "<?= $SLANG['BadFormat'] ?>";
    }
    else if ( form.elements['newMonitor[Type]'].value == 'Remote' )
    {
        if ( !form.elements['newMonitor[Host]'].value || !form.elements['newMonitor[Host]'].value.match( /^[0-9a-zA-Z_.:@-]+$/ ) )
            errors[errors.length] = "<?= $SLANG['BadHost'] ?>";
        if ( form.elements['newMonitor[Port]'].value && !form.elements['newMonitor[Port]'].value.match( /^\d+$/ ) )
            errors[errors.length] = "<?= $SLANG['BadPort'] ?>";
        //if ( !form.elements['newMonitor[Path]'].value )
            //errors[errors.length] = "<?= $SLANG['BadPath'] ?>";
    }
    else if ( form.elements['newMonitor[Type]'].value == 'File' )
    {
        if ( !form.elements['newMonitor[Path]'].value )
            errors[errors.length] = "<?= $SLANG['BadPath'] ?>";
    }
    if ( !form.elements['newMonitor[Width]'].value || !(parseInt(form.elements['newMonitor[Width]'].value) > 0 ) )
        errors[errors.length] = "<?= $SLANG['BadWidth'] ?>";
    if ( !form.elements['newMonitor[Height]'].value || !(parseInt(form.elements['newMonitor[Height]'].value) > 0 ) )
        errors[errors.length] = "<?= $SLANG['BadHeight'] ?>";
    if ( !form.elements['newMonitor[LabelX]'].value || !(parseInt(form.elements['newMonitor[LabelX]'].value) >= 0 ) )
        errors[errors.length] = "<?= $SLANG['BadLabelX'] ?>";
    if ( !form.elements['newMonitor[LabelY]'].value || !(parseInt(form.elements['newMonitor[LabelY]'].value) >= 0 ) )
        errors[errors.length] = "<?= $SLANG['BadLabelY'] ?>";
    if ( !form.elements['newMonitor[ImageBufferCount]'].value || !(parseInt(form.elements['newMonitor[ImageBufferCount]'].value) >= 10 ) )
        errors[errors.length] = "<?= $SLANG['BadImageBufferCount'] ?>";
    if ( !form.elements['newMonitor[WarmupCount]'].value || !(parseInt(form.elements['newMonitor[WarmupCount]'].value) >= 0 ) )
        errors[errors.length] = "<?= $SLANG['BadWarmupCount'] ?>";
    if ( !form.elements['newMonitor[PreEventCount]'].value || !(parseInt(form.elements['newMonitor[PreEventCount]'].value) > 0 ) || (parseInt(form.elements['newMonitor[PreEventCount]'].value) > parseInt(form.elements['newMonitor[ImageBufferCount]'].value)) )
        errors[errors.length] = "<?= $SLANG['BadPreEventCount'] ?>";
    if ( !form.elements['newMonitor[PostEventCount]'].value || !(parseInt(form.elements['newMonitor[PostEventCount]'].value) >= 0 ) )
        errors[errors.length] = "<?= $SLANG['BadPostEventCount'] ?>";
    if ( !form.elements['newMonitor[StreamReplayBuffer]'].value || !(parseInt(form.elements['newMonitor[StreamReplayBuffer]'].value) >= 0 ) )
        errors[errors.length] = "<?= $SLANG['BadStreamReplayBuffer'] ?>";
    if ( !form.elements['newMonitor[AlarmFrameCount]'].value || !(parseInt(form.elements['newMonitor[AlarmFrameCount]'].value) > 0 ) )
        errors[errors.length] = "<?= $SLANG['BadAlarmFrameCount'] ?>";
    if ( !form.elements['newMonitor[SectionLength]'].value || !(parseInt(form.elements['newMonitor[SectionLength]'].value) >= 30 ) )
        errors[errors.length] = "<?= $SLANG['BadSectionLength'] ?>";
    if ( !form.elements['newMonitor[FPSReportInterval]'].value || !(parseInt(form.elements['newMonitor[FPSReportInterval]'].value) >= 100 ) )
        errors[errors.length] = "<?= $SLANG['BadFPSReportInterval'] ?>";
    if ( !form.elements['newMonitor[FrameSkip]'].value || !(parseInt(form.elements['newMonitor[FrameSkip]'].value) >= 0 ) )
        errors[errors.length] = "<?= $SLANG['BadFrameSkip'] ?>";
    if ( form.elements['newMonitor[Type]'].value == 'Local' )
        if ( !form.elements['newMonitor[SignalCheckColour]'].value || !form.elements['newMonitor[SignalCheckColour]'].value.match( /^[#0-9a-zA-Z]+$/ ) )
            errors[errors.length] = "<?= $SLANG['BadSignalCheckColour'] ?>";
    if ( !form.elements['newMonitor[WebColour]'].value || !form.elements['newMonitor[WebColour]'].value.match( /^[#0-9a-zA-Z]+$/ ) )
        errors[errors.length] = "<?= $SLANG['BadWebColour'] ?>";
    if ( errors.length )
    {
        alert( errors.join( "\n" ) );
        return( false );
    }
    return( true );
}

function updateMethods( element )
{
    var form = element.form;

    form.elements['newMonitor[Method]'].length = 0;
    switch ( form.elements['newMonitor[Protocol]'].value )
    {
        case 'http' :
        {
<?php
foreach( $httpMethods as $label=>$value )
{
?>
            form.elements['newMonitor[Method]'].options[form.elements['newMonitor[Method]'].length] = new Option( "<?= $value ?>", "<?= htmlspecialchars($label) ?>" );
<?php
}
?>
            $(form.elements['newMonitor[SubPath]']).getAncestor( 'tr' ).addClass( 'hidden' );
            break;
        }
        case 'rtsp' :
        {
<?php
foreach( $rtspMethods as $label=>$value )
{
?>
            form.elements['newMonitor[Method]'].options[form.elements['newMonitor[Method]'].length] = new Option( "<?= $value ?>", "<?= htmlspecialchars($label) ?>" );
<?php
}
?>
            $(form.elements['newMonitor[SubPath]']).getAncestor( 'tr' ).removeClass( 'hidden' );
            break;
        }
    }
    return( true );
}

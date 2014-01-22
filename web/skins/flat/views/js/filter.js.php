var deleteSavedFilterString = "<?= $SLANG['DeleteSavedFilter'] ?>";

function validateForm( form )
{
<?php
if ( count($_REQUEST['filter']['terms']) > 2 )
{
?>
    var bracket_count = 0;
<?php
for ( $i = 0; $i < count($_REQUEST['filter']['terms']); $i++ )
{
?>
    var obr = form.elements['filter[terms][<?= $i ?>][obr]'];
    var cbr = form.elements['filter[terms][<?= $i ?>][cbr]'];
    bracket_count += parseInt(obr.options[obr.selectedIndex].value);
    bracket_count -= parseInt(cbr.options[cbr.selectedIndex].value);
<?php
}
?>
    if ( bracket_count )
    {
        alert( "<?= $SLANG['ErrorBrackets'] ?>" );
        return( false );
    }
<?php
}
?>
<?php
for ( $i = 0; $i < count($_REQUEST['filter']['terms']); $i++ )
{
?>
    var val = form.elements['filter[terms][<?= $i ?>][val]'];
    if ( val.value == '' )
    {
        alert( "<?= $SLANG['ErrorValidValue'] ?>" );
        return( false );
    }
<?php
}
?>
    return( true );
}
<?php
if ( !empty($hasCal) )
{
?>
</script>
<style type="text/css">@import url(tools/jscalendar/calendar-win2k-1.css);</style>
<script type="text/javascript" src="tools/jscalendar/calendar.js"></script>
<script type="text/javascript" src="tools/jscalendar/lang/calendar-en.js"></script>
<script type="text/javascript" src="tools/jscalendar/calendar-setup.js"></script>
<script type="text/javascript">
// Empty
<?php
}

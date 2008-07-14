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
</script>
<?php
if ( !empty($hasCal) )
{
?>
<style type="text/css">@import url(calendar/calendar-win2k-1.css);</style>
<script type="text/javascript" src="calendar/calendar.js"></script>
<script type="text/javascript" src="calendar/lang/calendar-en.js"></script>
<script type="text/javascript" src="calendar/calendar-setup.js"></script>
<?php
}

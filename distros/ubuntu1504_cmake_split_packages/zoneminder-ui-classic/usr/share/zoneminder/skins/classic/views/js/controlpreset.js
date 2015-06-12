function updateLabel()
{
    var presetIndex = $('contentForm').preset.getValue();
    if ( labels[presetIndex] )
    {
        $('contentForm').newLabel.value = labels[presetIndex];
    }
    else
    {
        $('contentForm').newLabel.value = "";
    }
}

window.addEvent( 'domready', updateLabel );

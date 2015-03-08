function validateForm( form )
{
    return( true );
}

function submitForm( form )
{
    form.submit();
}

function saveChanges( element )
{
    var form = element.form;
    if ( validateForm( form ) )
    {
        submitForm( form );
        return( true );
    }
    return( false );
}

function applyChanges()
{
    var form = document.pluginForm;
    for ( var option in pluginOptionList )
    {
        // Sync hidden field
        if ( form.elements['dsp_pluginOpt[' + option + ']'].type == "checkbox" )
        {
            if ( form.elements['dsp_pluginOpt[' + option + ']'].checked )
            {
                form.elements['pluginOpt[' + option + ']'].value = "Yes";
            }
            else
            {
                form.elements['pluginOpt[' + option + ']'].value = "No";
            }
        }
        else
        {
            form.elements['pluginOpt[' + option + ']'].value = form.elements['dsp_pluginOpt[' + option  + ']'].value;
        }
        var enabled = true;
        // Disable visible field if a dependency is missing
        for ( var name in pluginOptionList[option] )
        {
            if (form.elements['pluginOpt[' + name + ']'].value != pluginOptionList[option][name])
            {
                form.elements['dsp_pluginOpt[' + option  + ']'].disabled = true;
                enabled = false;
                break;
            }
        }
        // Enable visible field if all dependencies are ok
        if (enabled)
        {
            form.elements['dsp_pluginOpt[' + option + ']'].disabled = false;
        }
    }
}

function limitRange( field, minValue, maxValue )
{
    var intval;
    if ( +field.value === parseInt(field.value) )
    {
        intval = parseInt(field.value);
    }
    else
    {
        alert("Not and integer!");
        field.value = minValue;
        return;
    }
    if ( intval < parseInt(minValue) )
    {
        field.value = minValue;
    }
    else if ( intval > parseInt(maxValue) )
    {
        field.value = maxValue;
    }
}

function initPage()
{
   return( true );
}

window.addEvent( 'domready', initPage );

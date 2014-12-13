function validateForm( form )
{
    return( true );
}

function submitForm( form )
{
    var cb = form.getElementsByTagName('input');
    for ( var i = 0; i < cb.length; i++)
    {
        if ( cb[i].type == 'checkbox' && !cb[i].checked )  // if this is an unchecked checkbox
        {
            cb[i].value = 0; // set the value to "off"
            cb[i].checked = true; // make sure it submits
        }
    }
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

function applyDependencies()
{
    var form = document.pluginForm;
    for ( var option in dependencies )
    {
       var enabled = true;
       for ( var name in dependencies[option] )
       {
          if (form.elements['pluginOpt[' + name + ']'].value != dependencies[option][name])
          {
             form.elements['pluginOpt[' + option  + ']'].disabled = true;
             enabled = false;
             break;
          }

       }
       if (enabled)
          form.elements['pluginOpt[' + option + ']'].disabled = false;
    }
}

function limitRange( field, minValue, maxValue )
{
    if ( parseInt(field.value) < parseInt(minValue) )
    {
        field.value = minValue;
    }
    else if ( parseInt(field.value) > parseInt(maxValue) )
    {
        field.value = maxValue;
    }
}

function initPage()
{
   return( true );
}

window.addEvent( 'domready', initPage );

function addOption( name )
{
    var form = document.pluginForm;
    var select = form.elements['dsp_pluginOpt[' + name + ']'];
    var str = form.elements['dsp_input_pluginOpt[' + name + ']'].value;
    // Do nothing if the input field is empty
    if ( str === "" )
    {
        return;
    }
    // Raise an error and exit of non alphanumeric characters in string
    if ( !str.match(/^[0-9a-zA-Z]+$/) )
    {
        alert(onlyAlphaCharString);
        return;
    }
    var hidden = form.elements['pluginOpt[' + name + ']'];
    // Do not add option if already present in list
    var list = hidden.value.split( "," );
    if ( list.indexOf( str ) != -1 )
    {
        alert(alreadyInList);
        return;
    }
    // Add option to the list
    select.options[select.options.length] = new Option(str, str);
    // Synchronize hidden field
    if ( hidden.value.length > 0 )
    {
        hidden.value += ",";
    }
    hidden.value += str;
    // Enable remove button
    form.elements['removeBtn[' + name + ']'].disabled = false;
}

function removeOptionSelected( name )
{
    var form = document.pluginForm;
    var select = form.elements['dsp_pluginOpt[' + name + ']'];
    // Remove selected options from list
    for ( var i = select.length - 1; i >= 0; i-- )
    {
        if ( select.options[i].selected )
        {
          select.remove(i);
        }
    }
    // Synchronize hidden field
    var hidden = form.elements['pluginOpt[' + name + ']'];
    hidden.value = "";
    for ( var i = 0; i < select.length; i++ )
    {
        if ( i > 0 )
        {
            hidden.value += ",";
        }
        hidden.value += select.options[i].value;
    }
    // Disable remove button if the list is empty
    if ( select.length == 0 )
    {
        form.elements['removeBtn[' + name + ']'].disabled = true;
    }
}

function updateAddBtn( name )
{
    var form = document.pluginForm;
    // Disable add button if the text input is empty
    if ( form.elements['dsp_input_pluginOpt[' + name + ']'].value === "")
    {
        form.elements['addBtn[' + name + ']'].disabled = true;
    }
    else
    {
        form.elements['addBtn[' + name + ']'].disabled = false;
    }
}

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
    // Synchronize hidden fields
    for ( var option in pluginOptionList )
    {
        //console.log("Option type is '" + form.elements['dsp_pluginOpt[' + option + ']'].type + "'");
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
        // Do not synchronize here lists with multiple selections, this should be done in function addOption or removeOptionSelected
        else if ( form.elements['dsp_pluginOpt[' + option + ']'].type != "select-multiple" )
        {
            form.elements['pluginOpt[' + option + ']'].value = form.elements['dsp_pluginOpt[' + option  + ']'].value;
        }
        //console.log("Option '" + option + "' synchronized, new value is '" + form.elements['pluginOpt[' + option + ']'].value + "'");
    }
    // Disable visible fields if a dependency is missing
    for ( var option in pluginOptionList )
    {
        var enabled = true;
        for ( var name in pluginOptionList[option] )
        {
            //console.log("form.elements['pluginOpt[" + name + "]'].value=" + form.elements['pluginOpt[' + name + ']'].value + " pluginOptionList[" + option + "][" + name + "]=" + pluginOptionList[option][name]);
            if (form.elements['pluginOpt[' + name + ']'].value != pluginOptionList[option][name])
            {
                form.elements['dsp_pluginOpt[' + option  + ']'].disabled = true;
                // Handle additionnal controls for list option
                if ( typeof form.elements['dsp_input_pluginOpt[' + option  + ']'] !== "undefined" )
                {
                    form.elements['dsp_input_pluginOpt[' + option  + ']'].disabled = true;
                    form.elements['dsp_input_pluginOpt[' + option  + ']'].value = "";
                    form.elements['addBtn[' + option  + ']'].disabled = true;
                    form.elements['removeBtn[' + option  + ']'].disabled = true;
                }
                enabled = false;
                break;
            }
        }
        // Enable visible field if all dependencies are ok
        if (enabled)
        {
            form.elements['dsp_pluginOpt[' + option + ']'].disabled = false;
            // Handle additionnal controls for list option
            if ( typeof form.elements['dsp_input_pluginOpt[' + option  + ']'] !== "undefined" )
            {
                form.elements['dsp_input_pluginOpt[' + option  + ']'].disabled = false;
                if ( form.elements['dsp_pluginOpt[' + option  + ']'].length > 0 )
                {
                    form.elements['removeBtn[' + option  + ']'].disabled = false;
                }
            }
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
        alert(onlyIntegerString);
        field.value = field.defaultValue;
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

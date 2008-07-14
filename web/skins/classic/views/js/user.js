function validateForm( form, newUser )
{
    var errors = new Array();
    if ( !form.elements['newUser[Username]'].value )
    {
        errors[errors.length] = "You must supply a username";
    }
    if ( form.elements['newUser[Password]'].value )
    {
        if ( !form.conf_password.value )
        {
            errors[errors.length] = "You must confirm the password";
        }
        else if ( form.elements['newUser[Password]'].value != form.conf_password.value )
        {
            errors[errors.length] = "The new and confirm passwords are different";
        }
    }
    else if ( newUser )
    {
        errors[errors.length] = "You must supply a password";
    }
    if ( errors.length )
    {
        alert( errors.join( "\n" ) );
        return( false );
    }
    return( true );
}

function selectRestrictedMonitors()
{
    createPopup( '?view=monitorselect&callForm=contentForm&callField=newUser[MonitorIds]', "zmRestrictedMonitors", 'monitorselect' );
}

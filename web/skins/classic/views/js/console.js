function setButtonStates( element )
{
    var form = element.form;
    var checked = 0;
    for ( var i = 0; i < form.elements.length; i++ )
    {
        if ( form.elements[i].type == "checkbox" )
        {
            if ( form.elements[i].checked )
            {
                if ( checked++ > 1 )
                    break;
            }
        }
    }
    $(element).getParent( 'tr' ).toggleClass( 'highlight' );
    form.editBtn.disabled = (checked!=1);
    form.deleteBtn.disabled = (checked==0);
}

function editMonitor( element )
{
    var form = element.form;
    for ( var i = 0; i < form.elements.length; i++ )
    {
        if ( form.elements[i].type == "checkbox" )
        {
            if ( form.elements[i].checked )
            {
                var monitorId = form.elements[i].value;
                createPopup( '?view=monitor&mid='+monitorId, 'zmMonitor'+monitorId, 'monitor' );
                form.elements[i].checked = false;
                setButtonStates( form.elements[i] );
                //$(form.elements[i]).getParent( 'tr' ).removeClass( 'highlight' );
                break;
            }
        }
    }
}

function deleteMonitor( element )
{
    if ( confirm( 'Warning, deleting a monitor also deletes all events and database entries associated with it.\nAre you sure you wish to delete?' ) )
    {
        var form = element.form;
        form.elements['action'].value = 'delete';
        form.submit();
    }
}

function reloadWindow()
{
    window.location.replace( thisUrl );
}

function initPage()
{
    reloadWindow.periodical( consoleRefreshTimeout );
    if ( showVersionPopup )
        createPopup( '?view=version', 'zmVersion', 'version' );
    if ( showDonatePopup )
        createPopup( '?view=donate', 'zmDonate', 'donate' );
}

window.addEvent( 'domready', initPage );

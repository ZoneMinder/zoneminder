function selectMonitors()
{
    createPopup( '?view=monitorselect&callForm=groupForm&callField=newGroup[MonitorIds]', 'zmMonitors', 'monitorselect' );
}

if ( refreshParent )
{
    opener.location.reload(true);
}

function configureButtons( element )
{
    if ( canEditGroups )
    {
        var form = element.form;
        if ( element.selected )
        {
            form.saveBtn.disabled = (element.value == 0);
        }
    }
}

window.focus();

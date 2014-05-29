function selectMonitors()
{
    createPopup( '?view=monitorselect&callForm=groupForm&callField=newGroup[MonitorIds]', 'zmMonitors', 'monitorselect' );
}

if ( refreshParent )
{
    opener.location.reload(true);
}

window.focus();

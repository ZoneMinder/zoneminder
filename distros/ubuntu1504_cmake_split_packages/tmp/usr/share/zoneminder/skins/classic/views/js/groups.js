function newGroup()
{
    createPopup( '?view=group', 'zmGroup', 'group' );
}

function editGroup( element )
{
    var form = element.form;
    form.action.value = 'setgroup';
    form.submit();
}

function editGroup( element )
{
    var form = element.form;
    for ( var i = 0; i < form.gid.length; i++ )
    {
        if ( form.gid[i].checked )
        {
            createPopup( '?view=group&gid='+form.gid[i].value, 'zmGroup', 'group' );
            return;
        }
    }
}

function deleteGroup( element )
{
    var form = element.form;
    form.view.value = currentView;
    form.action.value = 'delete';
    form.submit();
}

function configureButtons( element )
{
    if ( canEditSystem )
    {
        var form = element.form;
        if ( element.checked )
        {
            form.editBtn.disabled = (element.value == 0);
            form.deleteBtn.disabled = (element.value == 0);
        }
    }
}

window.focus();

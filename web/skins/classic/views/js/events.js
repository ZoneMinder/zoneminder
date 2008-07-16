function closeWindows()
{
    window.close();
    // This is a hack. The only way to close an existing window is to try and open it!
    var filterWindow = window.open( thisUrl+'?view=none', 'zmFilter', 'width=1,height=1' );
    filterWindow.close();
}

function toggleCheckbox( element, name )
{
    var form = element.form;
    var checked = element.checked;
    for (var i = 0; i < form.elements.length; i++)
        if (form.elements[i].name.indexOf(name) == 0)
            form.elements[i].checked = checked;
    form.viewBtn.disabled = !checked;
    form.editBtn.disabled = !checked;
    form.archiveBtn.disabled = unarchivedEvents?!checked:true;
    form.unarchiveBtn.disabled = archivedEvents?!checked:true;
    form.exportBtn.disabled = !checked;
    form.deleteBtn.disabled = !checked;
}

function configureButton( element, name )
{
    var form = element.form;
    var checked = element.checked;
    if ( !checked )
    {
        for (var i = 0; i < form.elements.length; i++)
        {
            if ( form.elements[i].name.indexOf(name) == 0)
            {
                if ( form.elements[i].checked )
                {
                    checked = true;
                    break;
                }
            }
        }
    }
    if ( !element.checked )
        form.toggleCheck.checked = false;
    form.viewBtn.disabled = !checked;
    form.editBtn.disabled = !checked;
    form.archiveBtn.disabled = (!checked)||(!unarchivedEvents);
    form.unarchiveBtn.disabled = (!checked)||(!archivedEvents);
    form.exportBtn.disabled = !checked;
    form.deleteBtn.disabled = !checked;
}

function deleteEvents( element, name )
{
    var form = element.form;
    var count = 0;
    for (var i = 0; i < form.elements.length; i++)
    {
        if (form.elements[i].name.indexOf(name) == 0)
        {
            if ( form.elements[i].checked )
            {
                count++;
                break;
            }
        }
    }
    if ( count > 0 )
    {
        if ( confirm( confirmDeleteEventsString ) )
        {
            form.action.value = 'delete';
            form.submit();
        }
    }
}

function editEvents( element, name )
{
    var form = element.form;
    var eids = new Array();
    for (var i = 0; i < form.elements.length; i++)
    {
        if (form.elements[i].name.indexOf(name) == 0)
        {
            if ( form.elements[i].checked )
            {
                eids[eids.length] = 'eids[]='+form.elements[i].value;
            }
        }
    }
    createPopup( '?view=eventdetail&'+eids.join( '&' ), 'zmEventDetail', 'eventdetail' );
}

function exportEvents( element, name )
{
    var form = element.form;
    var eids = new Array();
    for (var i = 0; i < form.elements.length; i++)
    {
        if (form.elements[i].name.indexOf(name) == 0)
        {
            if ( form.elements[i].checked )
            {
                eids[eids.length] = 'eids[]='+form.elements[i].value;
            }
        }
    }
    createPopup( '?view=export&'+eids.join( '&' ), 'zmExport', 'export' );
}

function viewEvents( element, name )
{
    var form = element.form;
    var events = new Array();
    for (var i = 0; i < form.elements.length; i++)
    {
        if ( form.elements[i].name.indexOf(name) == 0)
        {
            if ( form.elements[i].checked )
            {
                events[events.length] = form.elements[i].value;
            }
        }
    }
    if ( events.length > 0 )
    {
        createPopup( '?view=event&eid='+events[0]+'&filter[terms][0][attr]=Id&&filter[terms][0][op]=%3D%5B%5D&&filter[terms][0][val]='+events.join('%2C')+sortQuery+'&page=1&play=1', 'zmEvent', 'event', maxWidth, maxHeight );
    }
}

function archiveEvents( element, name )
{
    var form = element.form;
    form.action.value = 'archive';
    form.submit();
}

function unArchiveEvents( element, name )
{
    var form = element.form;
    form.action.value = 'unarchive';
    form.submit();
}

if ( openFilterWindow )
{
    //opener.location.reload(true);
    createPopup( '?view=filter&page='+thisPage+filterQuery, 'zmFilter', 'filter' );
    location.replace( '?view='+currentView+'&page='+thisPage+filterQuery );
}

function updateButtons( element )
{
    var form = element.form;

    if ( element.type == 'checkbox' && element.checked )
        form.elements['executeButton'].disabled = false;
    else
    {
        var canExecute = false;
        if ( form.elements['autoArchive'].checked )
            canExecute = true;
        if ( form.elements['autoVideo'].checked )
            canExecute = true;
        if ( form.elements['autoUpload'].checked )
            canExecute = true;
        if ( form.elements['autoEmail'].checked )
            canExecute = true;
        if ( form.elements['autoMessage'].checked )
            canExecute = true;
        if ( form.elements['autoExecute'].checked && form.elements['autoExecuteCmd'].value != '' )
            canExecute = true;
        if ( form.elements['autoDelete'].checked )
            canExecute = true;
        form.elements['executeButton'].disabled = !canExecute;
    }
}

function clearValue( element, line )
{
    var form = element.form;
    var val = form.elements['filter[terms]['+line+'][val]'];
    val.value = '';
}

function submitToFilter( element, reload )
{
    var form = element.form;
    form.target = window.name;
    form.view.value = 'filter';
    form.reload.value = reload;
    form.submit();
}

function submitToEvents( element )
{
    var form = element.form;
    if ( validateForm( form ) )
    {
        form.target = 'zmEvents';
        form.view.value = 'events';
        form.action.value = '';
        form.execute.value = 0;
        form.submit();
    }
}

function executeFilter( element )
{
    var form = element.form;
    if ( validateForm( form ) )
    {
        form.target = 'zmEvents';
        form.view.value = 'events';
        form.action.value = 'filter';
        form.execute.value = 1;
        form.submit();
    }
}

function saveFilter( element )
{
    var form = element.form;

    var popupName = 'zmEventsFilterSave';
    createPopup( thisUrl, popupName, 'filtersave' );

    form.target = popupName;
    form.view.value = 'filtersave';
    form.submit();
}

function deleteFilter( element, name )
{
    if ( confirm( deleteSavedFilterString+" '"+name+"'" ) )
    {
        var form = element.form;
        form.action.value = 'delete';
        form.fid.value = name;
        submitToFilter( element, 1 );
    }
}

function addTerm( element, line )
{
    var form = element.form;
    form.target = window.name;
    form.view.value = currentView;
    form.action.value = 'filter';
    form.subaction.value = 'addterm';
    form.line.value = line;
    form.submit();
}

function delTerm( element, line )
{
    var form = element.form;
    form.target = window.name;
    form.view.value = currentView;
    form.action.value = 'filter';
    form.subaction.value = 'delterm';
    form.line.value = line;
    form.submit();
}

function init()
{
    updateButtons( $('executeButton') );
}

window.addEvent( 'domready', init );

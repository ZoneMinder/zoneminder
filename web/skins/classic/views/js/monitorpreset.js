function submitPreset( element )
{
    var form = element.form;
    form.target = opener.name;
    form.view.value = 'monitor';
    form.submit();
    closeWindow.delay( 250 );
}

function configureButtons( this )
{
    var form = element.form;
    form.saveBtn.disabled = (form.preset.selectedIndex==0);
}

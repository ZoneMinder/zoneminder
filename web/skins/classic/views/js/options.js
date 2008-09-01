function configureButton( element )
{
    var form = element.form;
    var checked = element.checked;
    if ( !element.checked )
    {
        for ( var i = 0; i < form.elements.length; i++ )
        {
            if ( form.elements[i].name.indexOf(element.name) == 0 )
            {
                if ( form.elements[i].checked )
                {
                    checked = true;
                    break;
                }
            }
        }
    }
    form.deleteBtn.disabled = !checked;
}

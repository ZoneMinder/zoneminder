function submitForm( element )
{
    var form = element.form;
    if ( option.selectedIndex == 0 )
        view.value = currentView;
    else
        view.value = 'none';
    form.submit();
}

if ( action == "donate" && option == "go" )
{
    zmWindow();
}

<?php

if ( empty($_REQUEST['mid']) )
{
    ajaxError( 'No monitor id supplied' );
}
elseif ( !isset($_REQUEST['zid']) )
{
    ajaxError( 'No zone id(s) supplied' );
}

ajaxError( 'Unrecognised action or insufficient permissions' );

?>

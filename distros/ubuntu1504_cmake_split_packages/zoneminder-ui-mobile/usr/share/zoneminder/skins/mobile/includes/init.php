<?php

ini_set( "arg_separator.output", "&amp;" );
ini_set( "url_rewriter.tags", "a=href,area=href,frame=src,input=src,fieldset=" );

$cookies = false;
if ( count($_COOKIE) || !empty($_REQUEST['cookies']) )
    $cookies = true;

if ( $cookies )
{
    ini_set( "session.use_cookies", "1" );
    ini_set( "session.use_trans_sid", "0" );
    ini_set( "url_rewriter.tags", "" );
}
else
{
    //ini_set( "session.auto_start", "1" );
    ini_set( "session.use_cookies", "0" );
    ini_set( "session.use_trans_sid", "1" );
}

if ( !isset($_SESSION['cookies']) )
{
    $_SESSION['cookies'] = $cookies;
    if ( $cookies )
        setcookie( "cookies", $cookies );
}
?>

<?php

// Change this to be whatever you want your default language to be
$default_lang = "en_gb";
$lang_file = 'zm_lang_'.ZM_LANG_DEFAULT.'.php';

if ( !file_exists( $lang_file ) )
{
	$lang_file = 'zm_lang_'.$default_lang.'.php';
}

require_once( $lang_file );

// Function to correlate the plurality string arrays with variable counts
// Note this still has to be used with printf etc to get the right formating
function zmVlang( $lang_var_array, $count )
{
	krsort( $lang_var_array );
	foreach ( $lang_var_array as $key=>$value )
	{
		if ( abs($count) >= $key )
		{
			return( $value );
		}
	}
	die( 'Error, unable to correlate variable language string' );
}

// Example
//$monitors = array();
//$monitors[] = 1;
//echo sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) );

?>

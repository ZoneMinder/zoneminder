<?php

$fallback_lang_file = 'zm_lang_en_gb.php';
$system_lang_file = 'zm_lang_'.ZM_LANG_DEFAULT.'.php';

if ( isset($user['Language']) )
{
	$user_lang_file = 'zm_lang_'.$user['Language'].'.php';
}

if ( isset($user_lang_file) && file_exists( $user_lang_file ) )
{
	$lang_file = $user_lang_file;
}
elseif ( file_exists( $system_lang_file ) )
{
	$lang_file = $system_lang_file;
}
else
{
	$lang_file = $fallback_lang_file;
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

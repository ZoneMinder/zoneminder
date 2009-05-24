<?php

error_reporting( E_ALL );

$files = array();
if ( $dir = opendir( "." ) )
{
    while ( ($file = readdir( $dir )) !== false )
    {
        if ( is_file( $file ) )
        {
            if ( preg_match( '/^.+_.+\.php$/', $file ) )
            {
                $files[] = $file;
            }
        }
    }
    closedir( $dir );
}

$modDate = strftime( "%Y-%m-%d" );
$termOffset = 23;
$commOffset = 24;

print( "Got ".count($files)." language files\n" );

require_once( 'en_gb.php' );

$enSLANG = $SLANG;
$enCLANG = $CLANG;
$enVLANG = $VLANG;

foreach ( $files as $file )
{
    unset( $SLANG );
    unset( $CLANG );
    unset( $VLANG );
    unset( $zmVlang );

    if ( $file == "en_gb.php" )
        continue;
    if ( $file == "en_us.php" )
        continue;

    print( "Processing $file\n" );

    $token = preg_replace( "/\s/", "", ucwords( preg_replace( "/_/", " ", basename( $file, ".php" ) ) ) );

    $code = $rawCode = file_get_contents( $file );
    $code = preg_replace( "/zmVlang/", "zmVlang".$token, $code );
    $code = preg_replace( "/^header.*$/m", "", $code );
    $code = preg_replace( "/^setlocale.*$/m", "", $code );

    $tmpFile = $file.".tmp";
    $newFile = $file.".new";

    if ( $fp = fopen( $tmpFile, "w" ) )
    {
        fwrite( $fp, $code );
        fclose( $fp );
    }

    require_once( $tmpFile );
    unlink( $tmpFile );

    $pattern = '/^(.+SLANG = array\(\n)(.+)(\);.+?)/sU';
    //echo "P:'$pattern'\n";
    if ( !preg_match( $pattern, $rawCode, $fileParts ) )
        die( "Can't find SLANG array\n" );
    //echo "F:'".$fileParts[2]."'\n";
    if ( !preg_match_all( "/(\s+.+)\n/", $fileParts[2], $matches ) )
        die( "Can't find SLANG terms\n" );
    
    $terms = $matches[1];
    $assocTerms = array();
    foreach( $terms as $term )
    {
        if ( !preg_match( "/\s+'(.+)'\s*=>/", $term, $matches ) )
            die( "Can't find term name in '$term'\n" );
        $assocTerms[$matches[1]] = $term;
    }
    foreach ( $enSLANG as $enName=>$enValue )
    {
        if ( empty($SLANG[$enName]) )
        {
            print( "Got missing token '".$enName."'\n" );
            $termPaddLen = max( $termOffset-(2+strlen($enName)), 0 );
            $commPaddLen = max( $commOffset-(2+strlen($enValue)), 0 );
            $assocTerms[$enName] = "    '".$enName."'".str_repeat(" ",$termPaddLen)."=> '$enValue', ".str_repeat(" ",$commPaddLen)."// Added - $modDate";
        }
    }
    foreach ( $SLANG as $name=>$value )
    {
        if ( empty($enSLANG[$name]) )
        {
            print( "Got extraneous token '".$name."'\n" );
            unset($assocTerms[$name]);
        }
    }
    ksort( $assocTerms, SORT_STRING );
    $newCode = $fileParts[1].join( "\n", array_values($assocTerms) )."\n".rtrim($fileParts[3])."\n";
    if ( $fp = fopen( $newFile, "w" ) )
    {
        fwrite( $fp, $newCode );
        fclose( $fp );
    }
    rename( $newFile, $file );
}

?>

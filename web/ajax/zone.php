<?php

if ( empty($_REQUEST['mid']) )
{
    ajaxError( 'No monitor id supplied' );
}
elseif ( !isset($_REQUEST['zid']) )
{
    ajaxError( 'No zone id(s) supplied' );
}

if ( canView( 'Monitors' ) )
{
    switch ( $_REQUEST['action'] )
    {
        case "zoneImage" :
        {
            $wd = getcwd();
            chdir( ZM_DIR_IMAGES );
            $hiColor = "0x00ff00";

            $command = getZmuCommand( " -m ".$_REQUEST['mid']." -z" );
            if ( !isset($_REQUEST['zid']) )
                $_REQUEST['zid'] = 0;
            $command .= "'".$_REQUEST['zid'].' '.$hiColor.' '.$_REQUEST['coords']."'";
            $status = exec( escapeshellcmd($command) );
            chdir( $wd );

            $monitor = dbFetchOne( "select * from Monitors where Id = '".dbEscape($_REQUEST['mid'])."'" );
            $points = coordsToPoints( $_REQUEST['coords'] );

            ajaxResponse( array(
                'zoneImage' => ZM_DIR_IMAGES.'/Zones'.$monitor['Id'].'.jpg?'.time(),
                'selfIntersecting' => isSelfIntersecting( $points ),
                'area' => getPolyArea( $points )
            ) );

            break;
        }
    }
}

ajaxError( 'Unrecognised action or insufficient permissions' );

?>

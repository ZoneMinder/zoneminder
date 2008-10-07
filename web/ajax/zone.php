<?php

header("Content-type: text/plain" );

$response = array(
    'result' => 'Ok',
    'x' => 1
);

if ( empty($_REQUEST['mid']) )
{
    $response['result'] = 'Error';
    $response['message'] = 'No monitor id supplied';
}
elseif ( !isset($_REQUEST['zid']) )
{
    $response['result'] = 'Error';
    $response['message'] = 'No zone id(s) supplied';
}

if ( $response['result'] != 'Error' )
{
    $refreshEvent = false;
    $refreshParent = false;

    if ( canEdit( 'Monitors' ) )
    {
        $response['result'] = 'Ok';
        switch ( $_REQUEST['action'] )
        {
            default :
            {
                $response['result'] = 'Error';
                break;
            }
        }
    }
    if ( canView( 'Monitors' ) )
    {
        $response['result'] = 'Ok';
        switch ( $_REQUEST['action'] )
        {
            case "zoneImage" :
            {
                $wd = getcwd();
                chdir( ZM_DIR_IMAGES );
                $hicolor = "0x00ff00";

                $command = getZmuCommand( " -m ".$_REQUEST['mid']." -z" );
                if ( !isset($_REQUEST['zid']) )
                    $_REQUEST['zid'] = 0;
                $command .= "'".$_REQUEST['zid'].' '.$hicolor.' '.$_REQUEST['coords']."'";
                $status = exec( escapeshellcmd($command) );
                chdir( $wd );

                //$response['zoneImage'] = ZM_DIR_IMAGES.'/Zones'.$_REQUEST['mid'].'.jpg?'.time();
                $monitor = dbFetchOne( "select * from Monitors where Id = '".dbEscape($_REQUEST['mid'])."'" );
                $response['zoneImage'] = ZM_DIR_IMAGES.'/Zones'.$monitor['Id'].'.jpg?'.time();

                $points = coordsToPoints( $_REQUEST['coords'] );
                $response['selfIntersecting'] = isSelfIntersecting( $points );
                $response['area'] = getPolyArea( $points );

                break;
            }
            default :
            {
                $response['result'] = 'Error';
                break;
            }
        }
    }
    if ( $response['result'] == 'Ok' )
    {
        $response['refreshParent'] = $refreshParent;
        $response['refreshEvent'] = $refreshEvent;
    }
    elseif ( !$response['message'] )
        $response['message'] = 'Unrecognised action or insufficient permissions';
}

echo jsValue( $response );

?>

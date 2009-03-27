<?php

if ( empty($_REQUEST['id']) && empty($_REQUEST['eids']) )
{
    ajaxError( "No event id(s) supplied" );
}

if ( canView( 'Events' ) )
{
    switch ( $_REQUEST['action'] )
    {
        case "video" :
        {
            if ( empty($_REQUEST['videoFormat']) )
            {
                ajaxError( "Video Generation Failure, no format given" );
            }
            elseif ( empty($_REQUEST['rate']) )
            {
                ajaxError( "Video Generation Failure, no rate given" );
            }
            elseif ( empty($_REQUEST['scale']) )
            {
                ajaxError( "Video Generation Failure, no scale given" );
            }
            else
            {
                $sql = "select E.*,M.Name as MonitorName,M.DefaultRate,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = ".dbEscape($_REQUEST['id']).monitorLimitSql();
                if ( !($event = dbFetchOne( $sql )) )
                    ajaxError( "Video Generation Failure, can't load event" );
                else
                    if ( $videoFile = createVideo( $event, $_REQUEST['videoFormat'], $_REQUEST['rate'], $_REQUEST['scale'], !empty($_REQUEST['overwrite']) ) )
                        ajaxResponse( array( 'response'=>$videoFile ) );
                    else
                        ajaxError( "Video Generation Failed" );
            }
            $ok = true;
            break;
        }
        case 'deleteVideo' :
        {
            unlink( $videoFiles[$_REQUEST['id']] );
            unset( $videoFiles[$_REQUEST['id']] );
            ajaxResponse();
            break;
        }
        case "export" :
        {
            require_once( ZM_SKIN_PATH.'/includes/export_functions.php' );

            if ( !empty($_REQUEST['exportDetail']) )
                $exportDetail = $_SESSION['export']['detail'] = $_REQUEST['exportDetail'];
            else
                $exportDetail = false;
            if ( !empty($_REQUEST['exportFrames']) )
                $exportFrames = $_SESSION['export']['frames'] = $_REQUEST['exportFrames'];
            else
                $exportFrames = false;
            if ( !empty($_REQUEST['exportImages']) )
                $exportImages = $_SESSION['export']['images'] = $_REQUEST['exportImages'];
            else
                $exportImages = false;
            if ( !empty($_REQUEST['exportVideo']) )
                $exportVideo = $_SESSION['export']['video'] = $_REQUEST['exportVideo'];
            else
                $exportVideo = false;
            if ( !empty($_REQUEST['exportMisc']) )
                $exportMisc = $_SESSION['export']['misc'] = $_REQUEST['exportMisc'];
            else
                $exportMisc = false;
            if ( !empty($_REQUEST['exportFormat']) )
                $exportFormat = $_SESSION['export']['format'] = $_REQUEST['exportFormat'];
            else
                $exportFormat = '';

            $exportIds = !empty($_REQUEST['eids'])?$_REQUEST['eids']:$_REQUEST['id'];
            if ( $exportFile = exportEvents( $exportIds, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc, $exportFormat ) )
                ajaxResponse( array( 'exportFile'=>$exportFile ) );
            else
                ajaxError( "Export Failed" );
            break;
        }
    }
}

if ( canEdit( 'Events' ) )
{
    switch ( $_REQUEST['action'] )
    {
        case "rename" :
        {
            if ( !empty($_REQUEST['eventName']) )
                dbQuery( "update Events set Name = '".dbEscape($_REQUEST['eventName'])."' where Id = '".dbEscape($_REQUEST['id'])."'" );
            else
                ajaxError( "No new event name supplied" );
            ajaxResponse( array( 'refreshEvent'=>true, 'refreshParent'=>true ) );
            break;
        }
        case "eventdetail" :
        {
            dbQuery( "update Events set Cause = '".dbEscape($_REQUEST['newEvent']['Cause'])."', Notes = '".dbEscape($_REQUEST['newEvent']['Notes'])."' where Id = '".dbEscape($_REQUEST['id'])."'" );
            ajaxResponse( array( 'refreshEvent'=>true, 'refreshParent'=>true ) );
            break;
        }
        case "archive" :
        case "unarchive" :
        {
            $archiveVal = ($_REQUEST['action'] == "archive")?1:0;
            dbQuery( "update Events set Archived = ".$archiveVal." where Id = '".dbEscape($_REQUEST['id'])."'" );
            ajaxResponse( array( 'refreshEvent'=>true, 'refreshParent'=>false ) );
            break;
        }
        case "delete" :
        {
            deleteEvent( dbEscape($_REQUEST['id']) );
            ajaxResponse( array( 'refreshEvent'=>false, 'refreshParent'=>true ) );
            break;
        }
    }
}

ajaxError( 'Unrecognised action or insufficient permissions' );

?>

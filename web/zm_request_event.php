<?php
// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( empty($_REQUEST['id']) || !canEdit( 'Events', $_REQUEST['id'] ) )
{
	$view = "error";
	return;
}

error_reporting( E_ALL );

if ( !$_REQUEST['id'] )
{
    error_log( "No event id supplied" );
    return;
}

$refresh_parent = false;

// Event scope actions, edit permissions required
if ( $_REQUEST['action'] == "rename" && $_REQUEST['id'] && $_REQUEST['eventName'] )
{
    dbQuery( "update Events set Name = '".dbEscape($_REQUEST['eventName'])."' where Id = '".dbEscape($_REQUEST['id'])."'" );
}
else if ( $_REQUEST['action'] == "eventdetail" )
{
    if ( $_REQUEST['id'] )
    {
        dbQuery( "update Events set Cause = '".dbEscape($_REQUEST['new_event']['Cause'])."', Notes = '".dbEscape($_REQUEST['new_event']['Notes'])."' where Id = '".dbEscape($_REQUEST['id'])."'" );
        $refresh_parent = true;
    }
}
elseif ( $_REQUEST['action'] == "archive" || $_REQUEST['action'] == "unarchive" )
{
    $archive_val = ($_REQUEST['action'] == "archive")?1:0;

    if ( $_REQUEST['id'] )
    {
        dbQuery( "update Events set Archived = ".$archive_val." where Id = '".dbEscape($_REQUEST['id'])."'" );
    }
    elseif ( $mark_eids || $mark_eid )
    {
        if ( !$mark_eids && $mark_eid )
        {
            $mark_eids[] = $mark_eid;
        }
        if ( $mark_eids )
        {
            foreach( $mark_eids as $mark_eid )
            {
                dbQuery( "update Events set Archived = $archive_val where Id = '$mark_eid'" );
            }
            $refresh_parent = true;
        }
    }
}
elseif ( $_REQUEST['action'] == "delete" )
{
    if ( !$mark_eids && $mark_eid )
    {
        $mark_eids[] = $mark_eid;
    }
    if ( $mark_eids )
    {
        foreach( $mark_eids as $mark_eid )
        {
            deleteEvent( $mark_eid );
        }
        $refresh_parent = true;
    }
}

$response['result'] = 'Ok';
$response['refreshParent'] = $refresh_parent;

header("Content-type: text/plain" );
echo jsValue( $response );

?>

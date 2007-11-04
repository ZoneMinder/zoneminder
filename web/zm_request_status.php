<?php
$spec = array(
    "system" => array(
        "permission" => "System",
        "table" => "Monitors",
        "limit" => 1,
        "elements" => array(
            "MonitorCount" => array( "sql" => "count(*)" ),
            "ActiveMonitorCount" => array( "sql" => "count(if(Function != 'None',1,NULL))" ),
            "State" => array( code => "daemonCheck()?'$zmSlangRunning':'$zmSlangStopped'" ),
            "Load" => array( code => "getLoad()" ),
            "Disk" => array( code => "getDiskPercent()" ),
        ),
    ),
    "monitor" => array(
        "permission" => "Monitors",
        "table" => "Monitors",
        "limit" => 1,
        "selector" => "Monitors.Id",
        "elements" => array(
            "Id" => array( "sql" => "Monitors.Id" ),
            "Name" => array( "sql" => "Monitors.Name" ),
    		"Type" => true,
    		"Function" => true,
    		"Enabled" => true,
    		"LinkedMonitors" => true,
    		"Triggers" => true,
    		"Device" => true,
    		"Channel" => true,
    		"Format" => true,
    		"Host" => true,
    		"Port" => true,
    		"Path" => true,
            "Width" => array( "sql" => "Monitors.Width" ),
            "Height" => array( "sql" => "Monitors.Height" ),
    		"Palette" => true,
    		"Orientation" => true,
    		"Brightness" => true,
    		"Contrast" => true,
    		"Hue" => true,
    		"Colour" => true,
    		"EventPrefix" => true,
    		"LabelFormat" => true,
    		"LabelX" => true,
    		"LabelY" => true,
    		"ImageBufferCount" => true,
    		"WarmupCount" => true,
    		"PreEventCount" => true,
    		"PostEventCount" => true,
    		"AlarmFrameCount" => true,
    		"SectionLength" => true,
    		"FrameSkip" => true,
    		"MaxFPS" => true,
    		"AlarmMaxFPS" => true,
    		"FPSReportInterval" => true,
    		"RefBlendPerc" => true,
    		"Controllable" => true,
    		"ControlId" => true,
    		"ControlDevice" => true,
    		"ControlAddress" => true,
    		"AutoStopTimeout" => true,
    		"TrackMotion" => true,
    		"TrackDelay" => true,
    		"ReturnLocation" => true,
    		"ReturnDelay" => true,
    		"DefaultView" => true,
    		"DefaultRate" => true,
    		"DefaultScale" => true,
    		"WebColour" => true,
    		"Sequence" => true,
            "MinEventId" => array( "sql" => "min(Events.Id)", "table" => "Events", join => "Events.MonitorId = Monitors.Id", "group" => "Events.MonitorId" ),
            "MaxEventId" => array( "sql" => "max(Events.Id)", "table" => "Events", join => "Events.MonitorId = Monitors.Id", "group" => "Events.MonitorId" ),
            "TotalEvents" => array( "sql" => "count(Events.Id)", "table" => "Events", join => "Events.MonitorId = Monitors.Id", "group" => "Events.MonitorId" ),
            "Status" => array( "zmu" => "-m ".$_REQUEST['id']." -s" ),
            "FrameRate" => array( "zmu" => "-m ".$_REQUEST['id']." -f" ),
        ),
    ),
    "events" => array(
        "permission" => "Events",
        "table" => "Events",
        "selector" => "Events.MonitorId",
        "elements" => array(
            "Id" => true,
            "Name" => true,
            "Cause" => true,
            "StartTime" => true,
            "EndTime" => true,
            "Width" => true,
            "Height" => true,
            "Length" => true,
            "Frames" => true,
            "AlarmFrames" => true,
            "TotScore" => true,
            "AvgScore" => true,
            "MaxScore" => true,
        ),
    ),
    "event" => array(
        "permission" => "Events",
        "table" => "Events",
        "limit" => 1,
        "selector" => "Events.Id",
        "elements" => array(
            "Id" => array( "sql" => "Events.Id" ),
            "MonitorId" => true,
            "Name" => true,
            "Cause" => true,
            "StartTime" => true,
            "StartTimeShort" => array( "sql" => "date_format( StartTime, '".STRF_FMT_DATETIME_SHORT."' )" ), 
            "EndTime" => true,
            "Width" => true,
            "Height" => true,
            "Length" => true,
            "Frames" => true,
            "AlarmFrames" => true,
            "TotScore" => true,
            "AvgScore" => true,
            "MaxScore" => true,
            "Archived" => true,
            "Videoed" => true,
            "Uploaded" => true,
            "Emailed" => true,
            "Messaged" => true,
            "Executed" => true,
            "LearnState" => true,
            "Notes" => true,
            "MinFrameId" => array( "sql" => "min(Frames.FrameId)", "table" => "Frames", "join" => "Events.Id = Frames.EventId", "group" => "Frames.EventId"  ),
            "MaxFrameId" => array( "sql" => "max(Frames.FrameId)", "table" => "Frames", "join" => "Events.Id = Frames.EventId", "group" => "Frames.EventId"  ),
            "MinFrameDelta" => array( "sql" => "min(Frames.Delta)", "table" => "Frames", "join" => "Events.Id = Frames.EventId", "group" => "Frames.EventId"  ),
            "MaxFrameDelta" => array( "sql" => "max(Frames.Delta)", "table" => "Frames", "join" => "Events.Id = Frames.EventId", "group" => "Frames.EventId"  ),
        ),
    ),
);

function throwError( $message, $code=400 )
{
    error_log( $message );
    header( "HTTP/1.0 $code $message" );
    exit();
}

function collectData( $spec )
{
    $element_spec = &$spec[strtolower($_REQUEST['entity'])];
    #print_r( $element_spec );
    if ( !canView( $element_spec['permission'] ) )
    {
        error_log( "Invalid permissions" );
	    return;
    }

    $data = array();

    $field_sql = array();
    $join_sql = array();
    $group_sql = array();

    $elements = &$element_spec['elements'];
    $lc_elements = array_change_key_case( $elements );
    if ( !isset($_REQUEST['element']) )
        $_REQUEST['element'] = array_keys( $elements );
    else if ( !is_array($_REQUEST['element']) )
        $_REQUEST['element'] = array( $_REQUEST['element'] );

    foreach ( $_REQUEST['element'] as $element )
    {
        if ( !($element_data = $lc_elements[strtolower($element)]) )
            throwError( "Bad ".$_REQUEST['entity']." element ".$element );
        if ( $element_data['code'] )
            $data[$element] = eval( "return( ".$element_data['code']." );" );
        else if ( $element_data['zmu'] )
            $data[$element] = exec( escapeshellcmd( getZmuCommand( " ".$element_data['zmu'] ) ) );
        else
        {
            if ( $element_data['sql'] )
                $field_sql[] = $element_data['sql']." as ".$element;
            else
                $field_sql[] = $element;
            if ( $element_data['table'] && $element_data['join'] )
            {
                $join_sql[] = "left join ".$element_data['table']." on ".$element_data['join'];
            }
            if ( $element_data['group'] )
            {
                $group_sql[] = $element_data['group'];
            }
        }
    }

    if ( count($field_sql) )
    {
        $sql = "select ".join( ", ", $field_sql )." from ".$element_spec['table'];
        if ( $join_sql )
            $sql .= " ".join( " ", array_unique( $join_sql ) );
        if ( $element_spec['selector'] )
            $sql .= " where ".$element_spec['selector']." = ".$_REQUEST['id'];
        if ( $group_sql )
            $sql .= " group by ".join( ",", array_unique( $group_sql ) );
        if ( $_REQUEST['sort'] )
            $sql .= " order by ".$_REQUEST['sort'];
        if ( $element_spec['limit'] )
            $limit = $element_spec['limit'];
        elseif ( $_REQUEST['count'] )
            $limit = $_REQUEST['count'];
        if ( !empty( $limit ) )
            $sql .= " limit ".$limit;
        if ( isset($limit) && $limit == 1 )
        {
            $sql_data = dbFetchOne( $sql );
            $data = array_merge( $data, $sql_data );
        }
        else
        {
            $count = 0;
            foreach( dbFetchAll( $sql ) as $sql_data )
            {
                $data[] = $sql_data;
                if ( ++$count >= $limit )
                    break;
            }
        }
    }
    #print_r( $data );
    return( $data );
}

$data = collectData( $spec );

if ( !isset($_REQUEST['layout']) )
{
    $_REQUEST['layout'] = "json";
}
switch( $_REQUEST['layout'] )
{
    case 'xml NOT CURRENTLY SUPPORTED' :
    {
        header("Content-type: application/xml" );
        echo( '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" );
        echo "<".strtolower($_REQUEST['entity']).">\n";
        foreach ( $data as $key=>$value )
        {
            $key = strtolower( $key );
            echo "<$key>".htmlentities($value)."</$key>\n";
        }
        echo "</".strtolower($_REQUEST['entity']).">\n";
        break;
    }
    case 'json' :
    {
        header("Content-type: text/plain" );
        $response = array( 'result'=>'Ok', $_REQUEST['entity'] => $data );
        echo jsValue( $response );
        break;
    }
    case 'text' :
    {
        header("Content-type: text/plain" );
        echo join( " ", array_values( $data ) );
        break;
    }
}
?>

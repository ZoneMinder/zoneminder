<?php

function getDateScale( $scales, $range, $minLines, $maxLines )
{
    foreach ( $scales as $scale )
    {
        $align = isset($scale['align'])?$scale['align']:1;
        $scaleRange = (int)($range/($scale['factor']*$align));
        //echo "S:".$scale['name'].", A:$align, SR:$scaleRange<br>";
        if ( $scaleRange >= $minLines )
        {
            $scale['range'] = $scaleRange;
            break;
        }
    }
    if ( !isset($scale['range']) )
    {
        $scale['range'] = (int)($range/($scale['factor']*$align));
    }
    $scale['divisor'] = 1;
    while ( ($scale['range']/$scale['divisor']) > $maxLines )
    {
        $scale['divisor']++;
    }
    $scale['lines'] = (int)($scale['range']/$scale['divisor']);
    return( $scale );
}

function getYScale( $range, $minLines, $maxLines )
{
    $scale['range'] = $range;
    $scale['divisor'] = 1;
    while ( $scale['range']/$scale['divisor'] > $maxLines )
    {
        $scale['divisor']++;
    }
    $scale['lines'] = (int)(($scale['range']-1)/$scale['divisor'])+1;

    return( $scale );
}

function getSlotFrame( $slot )
{
    $slotFrame = isset($slot['frame'])?$slot['frame']['FrameId']:1;
    if ( false && $slotFrame )
    {
        $slotFrame -= $monitor['PreEventCount'];
        if ( $slotFrame < 1 )
            $slotFrame = 1;
    }
    return( $slotFrame );
}

function parseFilterToTree( $filter )
{
    if ( count($filter['terms']) > 0 )
    {
        $postfixExpr = array();
        $postfixStack = array();

        $priorities = array(
            '<' => 1,
            '<=' => 1,
            '>' => 1,
            '>=' => 1,
            '=' => 2,
            '!=' => 2,
            '=~' => 2,
            '!~' => 2,
            '=[]' => 2,
            '![]' => 2,
            'and' => 3,
            'or' => 4,
        );

        for ( $i = 0; $i <= count($filter['terms']); $i++ )
        {
            if ( !empty($filter['terms'][$i]['cnj']) )
            {
                while( true )
                {
                    if ( !count($postfixStack) )
                    {
                        $postfixStack[] = array( 'type'=>"cnj", 'value'=>$filter['terms'][$i]['cnj'], 'sqlValue'=>$filter['terms'][$i]['cnj']);
                        break;
                    }
                    elseif ( $postfixStack[count($postfixStack)-1]['type'] == 'obr' )
                    {
                        $postfixStack[] = array( 'type'=>"cnj", 'value'=>$filter['terms'][$i]['cnj'], 'sqlValue'=>$filter['terms'][$i]['cnj']);
                        break;
                    }
                    elseif ( $priorities[$filter['terms'][$i]['cnj']] < $priorities[$postfixStack[count($postfixStack)-1]['value']] )
                    {
                        $postfixStack[] = array( 'type'=>"cnj", 'value'=>$filter['terms'][$i]['cnj'], 'sqlValue'=>$filter['terms'][$i]['cnj']);
                        break;
                    }
                    else
                    {
                        $postfixExpr[] = array_pop( $postfixStack );
                    }
                }
            }
            if ( !empty($filter['terms'][$i]['obr']) )
            {
                for ( $j = 0; $j < $filter['terms'][$i]['obr']; $j++ )
                {
                    $postfixStack[] = array( 'type'=>"obr", 'value'=>$filter['terms'][$i]['obr']);
                }
            }
            if ( !empty($filter['terms'][$i]['attr']) )
            {
                $dtAttr = false;
                switch ( $filter['terms'][$i]['attr'])
                {
                    case 'MonitorName':
                        $sqlValue = 'M.'.preg_replace( '/^Monitor/', '', $filter['terms'][$i]['attr']);
                        break;
                    case 'Name':
                        $sqlValue = "E.Name";
                        break;
                    case 'Cause':
                        $sqlValue = "E.Cause";
                        break;
                    case 'DateTime':
                        $sqlValue = "E.StartTime";
                        $dtAttr = true;
                        break;
                    case 'Date':
                        $sqlValue = "to_days( E.StartTime )";
                        $dtAttr = true;
                        break;
                    case 'Time':
                        $sqlValue = "extract( hour_second from E.StartTime )";
                        break;
                    case 'Weekday':
                        $sqlValue = "weekday( E.StartTime )";
                        break;
                    case 'Id':
                    case 'Name':
                    case 'MonitorId':
                    case 'Length':
                    case 'Frames':
                    case 'AlarmFrames':
                    case 'TotScore':
                    case 'AvgScore':
                    case 'MaxScore':
                    case 'Archived':
                        $sqlValue = "E.".$filter['terms'][$i]['attr'];
                        break;
                    case 'DiskPercent':
                        $sqlValue = getDiskPercent();
                        break;
                    case 'DiskBlocks':
                        $sqlValue = getDiskBlocks();
                        break;
                    default :
                        $sqlValue = $filter['terms'][$i]['attr'];
                        break;
                }
                if ( $dtAttr )
                {
                    $postfixExpr[] = array( 'type'=>"attr", 'value'=>$filter['terms'][$i]['attr'], 'sqlValue'=>$sqlValue, 'dtAttr'=>true );
                }
                else
                {
                    $postfixExpr[] = array( 'type'=>"attr", 'value'=>$filter['terms'][$i]['attr'], 'sqlValue'=>$sqlValue );
                }
            }
            if ( isset($filter['terms'][$i]['op']) )
            {
                if ( empty($filter['terms'][$i]['op']) )
                {
                    $filter['terms'][$i]['op' ]= '=';
                }
                switch ( $filter['terms'][$i]['op' ])
                {
                    case '=' :
                    case '!=' :
                    case '>=' :
                    case '>' :
                    case '<' :
                    case '<=' :
                        $sqlValue = $filter['terms'][$i]['op'];
                        break;
                    case '=~' :
                        $sqlValue = "regexp";
                        break;
                    case '!~' :
                        $sqlValue = "not regexp";
                        break;
                    case '=[]' :
                        $sqlValue = 'in (';
                        break;
                    case '![]' :
                        $sqlValue = 'not in (';
                        break;
                }
                while( true )
                {
                    if ( !count($postfixStack) )
                    {
                        $postfixStack[] = array( 'type'=>"op", 'value'=>$filter['terms'][$i]['op'], 'sqlValue'=>$sqlValue );
                        break;
                    }
                    elseif ( $postfixStack[count($postfixStack)-1]['type'] == 'obr' )
                    {
                        $postfixStack[] = array( 'type'=>"op", 'value'=>$filter['terms'][$i]['op'], 'sqlValue'=>$sqlValue );
                        break;
                    }
                    elseif ( $priorities[$filter['terms'][$i]['op']] < $priorities[$postfixStack[count($postfixStack)-1]['value']] )
                    {
                        $postfixStack[] = array( 'type'=>"op", 'value'=>$filter['terms'][$i]['op'], 'sqlValue'=>$sqlValue );
                        break;
                    }
                    else
                    {
                        $postfixExpr[] = array_pop( $postfixStack );
                    }
                }
            }
            if ( isset($filter['terms'][$i]['val']) )
            {
                $valueList = array();
                foreach ( preg_split( '/["\'\s]*?,["\'\s]*?/', preg_replace( '/^["\']+?(.+)["\']+?$/', '$1', $filter['terms'][$i]['val' ]) ) as $value )
                {
                    switch ( $filter['terms'][$i]['attr'])
                    {
                        case 'MonitorName':
                        case 'Name':
                        case 'Cause':
                            $value = "'$value'";
                            break;
                        case 'DateTime':
                            $value = "'".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."'";
                            break;
                        case 'Date':
                            $value = "to_days( '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
                            break;
                        case 'Time':
                            $value = "extract( hour_second from '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
                            break;
                        case 'Weekday':
                            $value = "weekday( '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
                            break;
                    }
                    $valueList[] = $value;
                }
                $postfixExpr[] = array( 'type'=>"val", 'value'=>$filter['terms'][$i]['val'], 'sqlValue'=>join( ',', $valueList ) );
            }
            if ( !empty($filter['terms'][$i]['cbr']) )
            {
                for ( $j = 0; $j < $filter['terms'][$i]['cbr']; $j++ )
                {
                    while ( count($postfixStack) )
                    {
                        $element = array_pop( $postfixStack );
                        if ( $element['type'] == "obr" )
                        {
                            $postfixExpr[count($postfixExpr)-1]['bracket'] = true;
                            break;
                        }
                        $postfixExpr[] = $element;
                    }
                }
            }
        }
        while ( count($postfixStack) )
        {
            $postfixExpr[] = array_pop( $postfixStack );
        }

        $exprStack = array();
        //foreach ( $postfixExpr as $element )
        //{
            //echo $element['value']." "; 
        //}
        //echo "<br>";
        foreach ( $postfixExpr as $element )
        {
            if ( $element['type'] == 'attr' || $element['type'] == 'val' )
            {
                $node = array( 'data'=>$element, 'count'=>0 );
                $exprStack[] = $node;
            }
            elseif ( $element['type'] == 'op' || $element['type'] == 'cnj' )
            {
                $right = array_pop( $exprStack );
                $left = array_pop( $exprStack );
                $node = array( 'data'=>$element, 'count'=>2+$left['count']+$right['count'], 'right'=>$right, 'left'=>$left );
                $exprStack[] = $node;
            }
            else
            {
                die( "Unexpected element type '".$element['type']."', value '".$element['value']."'" );
            }
        }
        if ( count($exprStack) != 1 )
        {
            die( "Expression stack has ".count($exprStack)." elements" );
        }
        $exprTree = array_pop( $exprStack );
        return( $exprTree );
    }
    return( false );
}

function _parseTreeToInfix( $node )
{
    $expression = '';
    if ( isset($node) )
    {
        if ( isset($node['left']) )
        {
            if ( !empty($node['data']['bracket']) )
                $expression .= '( ';
            $expression .= _parseTreeToInfix( $node['left'] );
        }
        $expression .= $node['data']['value']." ";
        if ( isset($node['right']) )
        {
            $expression .= _parseTreeToInfix( $node['right'] );
            if ( !empty($node['data']['bracket']) )
                $expression .= ') ';
        }
    }
    return( $expression );
}

function parseTreeToInfix( $tree )
{
    return( _parseTreeToInfix( $tree ) );
}

function _parseTreeToSQL( $node, $cbr=false )
{
    $expression = '';
    if ( $node )
    {
        if ( isset($node['left']) )
        {
            if ( !empty($node['data']['bracket']) )
                $expression .= '( ';
            $expression .= _parseTreeToSQL( $node['left'] );
        }
        $inExpr = $node['data']['type'] == 'op' && ($node['data']['value'] == '=[]' || $node['data']['value'] == '![]');
        $expression .= $node['data']['sqlValue'];
        if ( !$inExpr )
            $expression .= ' ';
        if ( $cbr )
            $expression .= ') ';
        if ( isset($node['right']) )
        {
            $expression .= _parseTreeToSQL( $node['right'], $inExpr );
            if ( !empty($node['data']['bracket']) )
                $expression .= ') ';
        }
    }
    return( $expression );
}

function parseTreeToSQL( $tree )
{
    return( _parseTreeToSQL( $tree ) );
}

function _parseTreeToFilter( $node, &$terms, &$level )
{
    $elements = array();
    if ( $node )
    {
        if ( isset($node['left']) )
        {
            if ( !empty($node['data']['bracket']) )
                $terms[$level]['obr'] = 1;
            _parseTreeToFilter( $node['left'], $terms, $level );
        }
        if ( $node['data']['type'] == 'cnj' )
        {
            $level++;
        }
        $terms[$level][$node['data']['type']] = $node['data']['value'];
        if ( isset($node['right']) )
        {
            _parseTreeToFilter( $node['right'], $terms, $level );
            if ( !empty($node['data']['bracket']) )
                $terms[$level]['cbr'] = 1;
        }
    }
}

function parseTreeToFilter( $tree )
{
    $terms = array();
    if ( isset($tree) )
    {
        $level = 0;
        _parseTreeToFilter( $tree, $terms, $level );
    }
    return( array( 'terms' => $terms ) );
}

function parseTreeToQuery( $tree )
{
    $filter = parseTreeToFilter( $tree );
    parseFilter( $filter );
    return( $filter['query'] );
}

function _drawTree( $node, $level )
{
    if ( isset($node['left']) )
    {
        _drawTree( $node['left'], $level+1 );
    }
    echo str_repeat( ".", $level*2 ).$node['data']['value']."<br>";
    if ( isset($node['right']) )
    {
        _drawTree( $node['right'], $level+1 );
    }
}

function drawTree( $tree )
{
    _drawTree( $tree, 0 );
}

function _extractDatetimeRange( &$node, &$minTime, &$maxTime, &$expandable, $subOr )
{
    $pruned = $leftPruned = $rightPruned = false;
    if ( $node )
    {
        if ( isset($node['left']) && isset($node['right']) )
        {
            if ( $node['data']['type'] == 'cnj' && $node['data']['value'] == 'or' )
            {
                $subOr = true;
            }
            elseif ( !empty($node['left']['data']['dtAttr']) )
            {
                if ( $subOr )
                {
                    $expandable = false;
                }
                elseif ( $node['data']['type'] == 'op' )
                {
                    if ( $node['data']['value'] == '>' || $node['data']['value'] == '>=' )
                    {
                        if ( !$minTime || $minTime > $node['right']['data']['sqlValue'] )
                        {
                            $minTime = $node['right']['data']['value'];
                            return( true );
                        }
                    }
                    if ( $node['data']['value'] == '<' || $node['data']['value'] == '<=' )
                    {
                        if ( !$maxTime || $maxTime < $node['right']['data']['sqlValue'] )
                        {
                            $maxTime = $node['right']['data']['value'];
                            return( true );
                        }
                    }
                }
                else
                {
                    die( "Unexpected node type '".$node['data']['type']."'" );
                }
                return( false );
            }

            $leftPruned = _extractDatetimeRange( $node['left'], $minTime, $maxTime, $expandable, $subOr );
            $rightPruned = _extractDatetimeRange( $node['right'], $minTime, $maxTime, $expandable, $subOr );

            if ( $leftPruned && $rightPruned )
            {
                $pruned = true;
            }
            elseif ( $leftPruned )
            {
                $node = $node['right'];
            }
            elseif ( $rightPruned )
            {
                $node = $node['left'];
            }
        }
    }
    return( $pruned );
}

function extractDatetimeRange( &$tree, &$minTime, &$maxTime, &$expandable )
{
    $minTime = "";
    $maxTime = "";
    $expandable = true;

    _extractDateTimeRange( $tree, $minTime, $maxTime, $expandable, false );
}

function appendDatetimeRange( &$tree, $minTime, $maxTime=false )
{
    $attrNode = array( 'data'=>array( 'type'=>'attr', 'value'=>'DateTime', 'sqlValue'=>'E.StartTime', 'dtAttr'=>true ), 'count'=>0 );
    $valNode = array( 'data'=>array( 'type'=>'val', 'value'=>$minTime, 'sqlValue'=>$minTime ), 'count'=>0 );
    $opNode = array( 'data'=>array( 'type'=>'op', 'value'=>'>=', 'sqlValue'=>'>=' ), 'count'=>2, 'left'=>$attrNode, 'right'=>$valNode );
    if ( isset($tree) )
    {
        $cnjNode = array( 'data'=>array( 'type'=>'cnj', 'value'=>'and', 'sqlValue'=>'and' ), 'count'=>2+$tree['count']+$opNode['count'], 'left'=>$tree, 'right'=>$opNode );
        $tree = $cnjNode;
    }
    else
    {
        $tree = $opNode;
    }

    if ( $maxTime )
    {
        $attrNode = array( 'data'=>array( 'type'=>'attr', 'value'=>'DateTime', 'sqlValue'=>'E.StartTime', 'dtAttr'=>true ), 'count'=>0 );
        $valNode = array( 'data'=>array( 'type'=>'val', 'value'=>$maxTime, 'sqlValue'=>$maxTime ), 'count'=>0 );
        $opNode = array( 'data'=>array( 'type'=>'op', 'value'=>'<=', 'sqlValue'=>'<=' ), 'count'=>2, 'left'=>$attrNode, 'right'=>$valNode );
        $cnjNode = array( 'data'=>array( 'type'=>'cnj', 'value'=>'and', 'sqlValue'=>'and' ), 'count'=>2+$tree['count']+$opNode['count'], 'left'=>$tree, 'right'=>$opNode );
        $tree = $cnjNode;
    }
}

?>

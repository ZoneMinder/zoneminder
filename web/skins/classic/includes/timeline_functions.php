<?php

function getDateScale($scales, $range, $minLines, $maxLines) {
	foreach ( $scales as $scale ) {
		$align = isset($scale['align'])?$scale['align']:1;
		$scaleRange = (int)($range/($scale['factor']*$align));
		//echo "S:".$scale['name'].", A:$align, SR:$scaleRange<br>";
		if ( $scaleRange >= $minLines ) {
			$scale['range'] = $scaleRange;
			break;
		}
	}
	if ( !isset($scale['range']) ) {
		$scale['range'] = (int)($range/($scale['factor']*$align));
	}
	$scale['divisor'] = 1;
	while ( ($scale['range']/$scale['divisor']) > $maxLines ) {
		$scale['divisor']++;
	}
	$scale['lines'] = (int)($scale['range']/$scale['divisor']);
	return $scale;
}

function getYScale($range, $minLines, $maxLines) {
	$scale['range'] = $range;
	$scale['divisor'] = 1;
	while ( $scale['range']/$scale['divisor'] > $maxLines ) {
		$scale['divisor']++;
	}
	$scale['lines'] = (int)(($scale['range']-1)/$scale['divisor'])+1;

	return $scale;
}

function getSlotFrame($slot) {
	$slotFrame = isset($slot['frame'])?$slot['frame']['FrameId']:1;
  # FIXME what's with this false?
	if ( false && $slotFrame ) {
		$slotFrame -= $monitor['PreEventCount'];
		if ( $slotFrame < 1 )
			$slotFrame = 1;
	}
	return $slotFrame;
}

function _parseTreeToInfix($node) {
	$expression = '';
	if ( isset($node) ) {
		if ( isset($node['left']) ) {
			if ( !empty($node['data']['bracket']) )
				$expression .= '( ';
			$expression .= _parseTreeToInfix($node['left']);
		}
		$expression .= $node['data']['value'].' ';
		if ( isset($node['right']) ) {
			$expression .= _parseTreeToInfix($node['right']);
			if ( !empty($node['data']['bracket']) )
				$expression .= ') ';
		}
	}
	return $expression;
}

function parseTreeToInfix($tree) {
	return _parseTreeToInfix($tree);
}

function _parseTreeToSQL($node, $cbr=false) {
	$expression = '';
	if ( !$node )
    return $expression;

  if ( isset($node['left']) ) {
    if ( !empty($node['data']['bracket']) )
      $expression .= '( ';
    $expression .= _parseTreeToSQL($node['left']);
  }
  $inExpr = $node['data']['type'] == 'op' && (
    $node['data']['value'] == '=[]'
    or
    $node['data']['value'] == '![]'
    or 
    $node['data']['value'] == 'IN'
    or 
    $node['data']['value'] == 'NOT IN'
  );
  $expression .= $node['data']['sqlValue'];
  if ( !$inExpr )
    $expression .= ' ';
  if ( $cbr )
    $expression .= ') ';
  if ( isset($node['right']) ) {
    $expression .= _parseTreeToSQL($node['right'], $inExpr);
    if ( !empty($node['data']['bracket']) )
      $expression .= ') ';
  } # end if right
  return $expression;
}

function parseTreeToSQL($tree) {
	return _parseTreeToSQL($tree);
}

function _parseTreeToFilter($node, &$terms, &$level) {
	$elements = array();
	if ( $node ) {
		if ( isset($node['left']) ) {
			if ( !empty($node['data']['bracket']) )
				$terms[$level]['obr'] = 1;
			_parseTreeToFilter( $node['left'], $terms, $level );
		}
		if ( $node['data']['type'] == 'cnj' ) {
			$level++;
		}
		$terms[$level][$node['data']['type']] = $node['data']['value'];
		if ( isset($node['right']) ) {
			_parseTreeToFilter($node['right'], $terms, $level);
			if ( !empty($node['data']['bracket']) )
				$terms[$level]['cbr'] = 1;
		}
	}
}

function parseTreeToFilter($tree) {
	$terms = array();
	if ( isset($tree) ) {
		$level = 0;
		_parseTreeToFilter($tree, $terms, $level);
	}
	return array('Query' => array('terms' => $terms));
}

function parseTreeToQuery($tree) {
	$filter = parseTreeToFilter($tree);
	parseFilter($filter, false, '&');
	return $filter['querystring'];
}

function _drawTree($node, $level) {
	if ( isset($node['left']) ) {
		_drawTree($node['left'], $level+1);
	}
	echo str_repeat('.', $level*2).$node['data']['value'].'<br/>';
	if ( isset($node['right']) ) {
		_drawTree($node['right'], $level+1);
	}
}

function drawTree($tree) {
	_drawTree($tree, 0);
}

function _extractDatetimeRange(&$node, &$minTime, &$maxTime, &$expandable, $subOr) {
	$pruned = $leftPruned = $rightPruned = false;
	if ( !($node and isset($node['left']) and isset($node['right']) ) ) {
    return $pruned;
  }

  if ( $node['data']['type'] == 'cnj' && $node['data']['value'] == 'or' ) {
    $subOr = true;
  } else if ( !empty($node['left']['data']['dtAttr']) ) {
    if ( $subOr ) {
      $expandable = false;
    } elseif ( $node['data']['type'] == 'op' ) {
      if ( $node['data']['value'] == '>' || $node['data']['value'] == '>=' ) {
        if ( !$minTime || $minTime > $node['right']['data']['sqlValue'] ) {
          $minTime = $node['right']['data']['value'];
          return true;
        }
      } else if ( $node['data']['value'] == '<' || $node['data']['value'] == '<=' ) {
        if ( !$maxTime || $maxTime < $node['right']['data']['sqlValue'] ) {
          $maxTime = $node['right']['data']['value'];
          return true;
        }
      }
    } else {
      ZM\Fatal("Unexpected node type '".$node['data']['type']."'");
    }
    return false;
  }

  $leftPruned = _extractDatetimeRange( $node['left'], $minTime, $maxTime, $expandable, $subOr );
  $rightPruned = _extractDatetimeRange( $node['right'], $minTime, $maxTime, $expandable, $subOr );

  if ( $leftPruned && $rightPruned ) {
    $pruned = true;
  } else if ( $leftPruned ) {
    $node = $node['right'];
  } else if ( $rightPruned ) {
    $node = $node['left'];
  }
	return $pruned;
}

function extractDatetimeRange( &$tree, &$minTime, &$maxTime, &$expandable ) {
	$minTime = '';
	$maxTime = '';
	$expandable = true;

	_extractDateTimeRange( $tree, $minTime, $maxTime, $expandable, false );
}

function appendDatetimeRange( &$tree, $minTime, $maxTime=false ) {
	$attrNode = array( 'data'=>array( 'type'=>'attr', 'value'=>'StartDateTime', 'sqlValue'=>'E.StartTime', 'dtAttr'=>true ), 'count'=>0 );
	$valNode = array( 'data'=>array( 'type'=>'val', 'value'=>$minTime, 'sqlValue'=>$minTime ), 'count'=>0 );
	$opNode = array( 'data'=>array( 'type'=>'op', 'value'=>'>=', 'sqlValue'=>'>=' ), 'count'=>2, 'left'=>$attrNode, 'right'=>$valNode );
	if ( isset($tree) ) {
		$cnjNode = array( 'data'=>array( 'type'=>'cnj', 'value'=>'and', 'sqlValue'=>'and' ), 'count'=>2+$tree['count']+$opNode['count'], 'left'=>$tree, 'right'=>$opNode );
		$tree = $cnjNode;
	} else {
		$tree = $opNode;
	}

	if ( $maxTime ) {
		$attrNode = array( 'data'=>array( 'type'=>'attr', 'value'=>'StartDateTime', 'sqlValue'=>'E.StartTime', 'dtAttr'=>true ), 'count'=>0 );
		$valNode = array( 'data'=>array( 'type'=>'val', 'value'=>$maxTime, 'sqlValue'=>$maxTime ), 'count'=>0 );
		$opNode = array( 'data'=>array( 'type'=>'op', 'value'=>'<=', 'sqlValue'=>'<=' ), 'count'=>2, 'left'=>$attrNode, 'right'=>$valNode );
		$cnjNode = array( 'data'=>array( 'type'=>'cnj', 'value'=>'and', 'sqlValue'=>'and' ), 'count'=>2+$tree['count']+$opNode['count'], 'left'=>$tree, 'right'=>$opNode );
		$tree = $cnjNode;
	}
}

?>

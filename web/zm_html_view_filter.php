<?php
//
// ZoneMinder web filter view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}
$select_name = "filter_name";
$filter_names = array( ''=>$zmSlangChooseFilter );
foreach ( dbFetchAll( "select * from Filters order by Name" ) as $row )
{
	$filter_names[$row['Name']] = $row['Name'];
    if ( $row['Background'] )
	    $filter_names[$row['Name']] .= "*";
	if ( !empty($reload) && isset($filter_name) && $filter_name == $row['Name'] )
	{
		$db_filter = $row;
	}
}

$background_str = "";
if ( isset($db_filter) )
{
	if ( $db_filter['Background'] ) 
		$background_str = '['.strtolower($zmSlangBackground).']';
    $filter = unserialize( $db_filter['Query'] );
    $sort_field = $filter['sort_field'];
    $sort_asc = $filter['sort_asc'];
    $limit = $filter['limit'];
    unset( $filter['sort_field'] );
    unset( $filter['sort_asc'] );
    unset( $filter['limit'] );
}

$conjunction_types = array(
	'and' => $zmSlangConjAnd,
	'or'  => $zmSlangConjOr
);
$obracket_types = array(); 
$cbracket_types = array();
for ( $i = 0; $i <= count($filter['terms'])-2; $i++ )
{
	$obracket_types[$i] = str_repeat( "(", $i );
	$cbracket_types[$i] = str_repeat( ")", $i );
}
$attr_types = array(
	'MonitorId'   => $zmSlangAttrMonitorId,
	'MonitorName' => $zmSlangAttrMonitorName,
	'Id'          => $zmSlangAttrId,
	'Name'        => $zmSlangAttrName,
	'Cause'       => $zmSlangAttrCause,
	'Notes'       => $zmSlangAttrNotes,
	'DateTime'    => $zmSlangAttrDateTime,
	'Date'        => $zmSlangAttrDate,
	'Time'        => $zmSlangAttrTime,
	'Weekday'     => $zmSlangAttrWeekday,
	'Length'      => $zmSlangAttrDuration,
	'Frames'      => $zmSlangAttrFrames,
	'AlarmFrames' => $zmSlangAttrAlarmFrames,
	'TotScore'    => $zmSlangAttrTotalScore,
	'AvgScore'    => $zmSlangAttrAvgScore,
	'MaxScore'    => $zmSlangAttrMaxScore,
	'Archived'    => $zmSlangAttrArchiveStatus,
	'DiskPercent' => $zmSlangAttrDiskPercent,
	'DiskBlocks'  => $zmSlangAttrDiskBlocks,
);
$op_types = array(
	'='   => $zmSlangOpEq,
	'!='  => $zmSlangOpNe,
	'>='  => $zmSlangOpGtEq,
	'>'   => $zmSlangOpGt,
	'<'   => $zmSlangOpLt,
	'<='  => $zmSlangOpLtEq,
	'=~'  => $zmSlangOpMatches,
	'!~'  => $zmSlangOpNotMatches,
	'=[]' => $zmSlangOpIn,
	'![]' => $zmSlangOpNotIn,
);
$archive_types = array(
	'0' => $zmSlangArchUnarchived,
	'1' => $zmSlangArchArchived
);
$weekdays = array();
for ( $i = 0; $i < 7; $i++ )
{
    $weekdays[$i] = strftime( "%A", mktime( 12, 0, 0, 1, $i+1, 2001 ) );
}
$sort_fields = array(
	'Id'          => $zmSlangAttrId,
	'Name'        => $zmSlangAttrName,
	'Cause'       => $zmSlangAttrCause,
	'Notes'       => $zmSlangAttrNotes,
	'MonitorName' => $zmSlangAttrMonitorName,
	'DateTime'    => $zmSlangAttrDateTime,
	'Length'      => $zmSlangAttrDuration,
	'Frames'      => $zmSlangAttrFrames,
	'AlarmFrames' => $zmSlangAttrAlarmFrames,
	'TotScore'    => $zmSlangAttrTotalScore,
	'AvgScore'    => $zmSlangAttrAvgScore,
	'MaxScore'    => $zmSlangAttrMaxScore,
);
$sort_dirns = array(
	'1' => $zmSlangSortAsc,
	'0'  => $zmSlangSortDesc
);
if ( empty($sort_field) )
{
	$sort_field = ZM_WEB_EVENT_SORT_FIELD; 
	$sort_asc = (ZM_WEB_EVENT_SORT_ORDER == "asc");
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEventFilter ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
function clearValue( form, line )
{
    var val = form.elements['filter[terms][<?= $i ?>][val]'];
    val.value = '';
}
function validateForm( form )
{
<?php
if ( count($filter['terms']) > 2 )
{
?>
	var bracket_count = 0;
<?php
for ( $i = 0; $i < count($filter['terms']); $i++ )
{
?>
    var obr = form.elements['filter[terms][<?= $i ?>][obr]'];
    var cbr = form.elements['filter[terms][<?= $i ?>][cbr]'];
	bracket_count += parseInt(obr.options[obr.selectedIndex].value);
	bracket_count -= parseInt(cbr.options[cbr.selectedIndex].value);
<?php
}
?>
	if ( bracket_count )
	{
		alert( "<?= $zmSlangErrorBrackets ?>" );
		return( false );
	}
<?php
}
?>
<?php
for ( $i = 0; $i < count($filter['terms']); $i++ )
{
?>
    var val = form.elements['filter[terms][<?= $i ?>][val]'];
	if ( val.value == '' )
	{
		alert( "<?= $zmSlangErrorValidValue ?>" );
		return( false );
	}
<?php
}
?>
	return( true );
}
function submitToFilter( form, reload )
{
	form.target = window.name;
	form.view.value = 'filter';
	form.reload.value = reload;
	form.submit();
}
function submitToEvents( form )
{
	var Name = 'zmEvents';

	form.target = Name;
	form.view.value = 'events';
	form.action.value = '';
	form.execute.value = 0;
	form.submit();
}
function executeFilter( form )
{
	var Name = 'zmEvents';

	form.target = Name;
	form.view.value = 'events';
	form.action.value = 'filter';
	form.execute.value = 1;
	form.submit();
}
function saveFilter( form )
{
	var Url = '<?= $PHP_SELF ?>';
	var Name = 'zmEventsFilterSave';
	var Width = <?= $jws['filtersave']['w'] ?>;
	var Height = <?= $jws['filtersave']['h'] ?>;
	var Options = 'resizable,scrollbars,width='+Width+',height='+Height;

	window.open( Url, Name, Options );
	form.target = Name;
	form.view.value = 'filtersave';
	form.submit();
}
function deleteFilter( form, name )
{
	if ( confirm( "<?= $zmSlangDeleteSavedFilter ?> '"+name+"'" ) )
	{
		form.action.value = 'delete';
		form.fid.value = name;
		submitToFilter( form, 1 );
	}
}
function addTerm( form, line )
{
	form.target = window.name;
	form.view.value = '<?= $view ?>';
	form.action.value = 'filter';
	form.subaction.value = 'addterm';
	form.line.value = line;
	form.submit();
}
function delTerm( form, line )
{
	form.target = window.name;
	form.view.value = '<?= $view ?>';
	form.action.value = 'filter';
	form.subaction.value = 'delterm';
	form.line.value = line;
	form.submit();
}
</script>
</head>
<body>
<form name="filter_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="filter">
<input type="hidden" name="page" value="<?= $page ?>">
<input type="hidden" name="reload" value="0">
<input type="hidden" name="execute" value="0">
<input type="hidden" name="action" value="">
<input type="hidden" name="subaction" value="">
<input type="hidden" name="line" value="">
<input type="hidden" name="fid" value="">
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="text"><?= $zmSlangUseFilter ?>:&nbsp;<?php if ( count($filter_names) > 1 ) { echo buildSelect( $select_name, $filter_names, "submitToFilter( document.filter_form, 1 );" ); } else { ?><select class="form" disabled><option><?= $zmSlangNoSavedFilters ?></option></select><?php } ?>&nbsp;&nbsp;<?= $background_str ?></td>
<?php if ( canEdit( 'Events' ) ) { ?>
<td align="right" class="text"><a href="javascript: saveFilter( document.filter_form );"><?= $zmSlangSave ?></a></td>
<?php } else { ?>
<td align="right" class="text">&nbsp;</td>
<?php } ?>
<?php if ( canEdit( 'Events' ) && isset($db_filter) ) { ?>
<td align="right" class="text"><a href="javascript: deleteFilter( document.filter_form, '<?= $db_filter['Name'] ?>' );"><?= $zmSlangDelete ?></a></td>
<?php } else { ?>
<td align="right" class="text">&nbsp;</td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr>
<td colspan="4" class="text"><hr width="100%"></td>
</tr>
<tr>
<td colspan="4">
<table width="100%" border="0" cellspacing="1" cellpadding="0">
<?php
for ( $i = 0; $i < count($filter['terms']); $i++ )
{
?>
<tr>
<?php
if ( $i == 0 )
{
?>
<td class="text">&nbsp;</td>
<?php
}
else
{
?>
<td class="text"><?= buildSelect( "filter[terms][$i][cnj]", $conjunction_types ); ?></td>
<?php
}
?>
<td class="text"><?php if ( count($filter['terms']) > 2 ) { echo buildSelect( "filter[terms][$i][obr]", $obracket_types ); } else { ?>&nbsp;<?php } ?></td>
<td class="text"><?= buildSelect( "filter[terms][$i][attr]", $attr_types, "clearValue( document.filter_form, $i ); submitToFilter( document.filter_form, 0 );" ); ?></td>
<?php if ( $filter['terms'][$i]['attr'] == "Archived" ) { ?>
<td class="text"><center><?= $zmSlangOpEq ?><input type="hidden" name="filter[terms][<?= $i ?>][op]" value="="></center></td>
<td class="text"><?= buildSelect( "filter[terms][$i][val]", $archive_types ); ?></td>
<?php } elseif ( $filter['terms'][$i]['attr'] == "Weekday" ) { ?>
<td class="text"><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
<td class="text"><?= buildSelect( "filter[terms][$i][val]", $weekdays ); ?></td>
<?php } elseif ( $filter['terms'][$i]['attr'] ) { ?>
<td class="text"><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
<td class="text"><input name="filter[terms][<?= $i ?>][val]" value="<?= $filter['terms'][$i]['val'] ?>" class="form" size="24"></td>
<?php } else { ?>
<td class="text"><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
<td class="text"><input name="filter[terms][<?= $i ?>][val]" value="<?= isset($filter['terms'][$i]['val'])?$filter['terms'][$i]['val']:'' ?>" class="form" size="24"></td>
<?php } ?>
<td class="text"><?php if ( count($filter['terms']) > 2 ) { echo buildSelect( "filter[terms][$i][cbr]", $cbracket_types ); } else { ?>&nbsp;<?php } ?></td>
<td class="text"><a href="javascript: addTerm(document.filter_form,<?= $i+1 ?>)">+</a><?php if ( $filter['terms'] > 1 ) { ?><a href="javascript: delTerm(document.filter_form,<?= $i ?>)">&ndash;</a><?php } ?></td>
</tr>
<?php
}
?>
</table>
</td>
</tr>
<tr>
<td colspan="4" class="text"><hr width="100%"></td>
</tr>
<tr>
<td colspan="4" class="text"><table width="100%" cellpadding="0" cellspacing="0"><tr><td class="text" align="left"><?= $zmSlangSortBy ?>&nbsp;<?= buildSelect( "sort_field", $sort_fields ); ?>&nbsp;<?= buildSelect( "sort_asc", $sort_dirns ); ?></td><td class="text" align="right"><?= $zmSlangLimitResultsPre ?> <input type="text" size="6" name="limit" value="<?= $limit ?>" class="form"> <?= $zmSlangLimitResultsPost ?></td></tr></table></td>
</tr>
<tr>
<td colspan="4" class="text"><hr width="100%"></td>
</tr>
<tr>
<td colspan="4" class="text"><table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="left" class="text"><?= $zmSlangFilterArchiveEvents ?>:&nbsp;</td>
<td align="left" class="text" colspan="2"><input type="checkbox" name="auto_archive" value="1"<?php if ( $db_filter['AutoArchive'] ) { echo " checked"; } ?> class="form-noborder"></td>
</tr>
<?php
if ( ZM_OPT_MPEG != "no" )
{
?>
<tr>
<td align="left" class="text"><?= $zmSlangFilterVideoEvents ?>:&nbsp;</td>
<td align="left" class="text" colspan="2"><input type="checkbox" name="auto_video" value="1"<?php if ( $db_filter['AutoVideo'] ) { echo " checked"; } ?> class="form-noborder"></td>
</tr>
<?php
}
if ( ZM_OPT_UPLOAD )
{
?>
<tr>
<td align="left" class="text"><?= $zmSlangFilterUploadEvents ?>:&nbsp;</td>
<td align="left" class="text" colspan="2"><input type="checkbox" name="auto_upload" value="1"<?php if ( $db_filter['AutoUpload'] ) { echo " checked"; } ?> class="form-noborder"></td>
</tr>
<?php
}
if ( ZM_OPT_EMAIL )
{
?>
<tr>
<td align="left" class="text"><?= $zmSlangFilterEmailEvents ?>:&nbsp;</td>
<td align="left" class="text" colspan="2"><input type="checkbox" name="auto_email" value="1"<?php if ( $db_filter['AutoEmail'] ) { echo " checked"; } ?> class="form-noborder"></td>
</tr>
<?php
}
if ( ZM_OPT_MESSAGE )
{
?>
<tr>
<td align="left" class="text"><?= $zmSlangFilterMessageEvents ?>:&nbsp;</td>
<td align="left" class="text" colspan="2"><input type="checkbox" name="auto_message" value="1"<?php if ( $db_filter['AutoMessage'] ) { echo " checked"; } ?> class="form-noborder"></td>
</tr>
<?php
}
?>
<tr>
<td align="left" class="text"><?= $zmSlangFilterExecuteEvents ?>:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_execute" value="1"<?php if ( $db_filter['AutoExecute'] ) { echo " checked"; } ?> class="form-noborder"></td>
<td align="left" class="text"><input type="text" name="auto_execute_cmd" value="<?= $db_filter['AutoExecuteCmd'] ?>" size="32" maxlength="255" class="form"></td>
</tr>
<tr>
<td align="left" class="text"><?= $zmSlangFilterDeleteEvents ?>:&nbsp;</td>
<td align="left" class="text" colspan="2"><input type="checkbox" name="auto_delete" value="1"<?php if ( $db_filter['AutoDelete'] ) { echo " checked"; } ?> class="form-noborder"></td>
</tr></table></td>
<tr>
<td colspan="4" class="text"><hr width="100%"></td>
</tr>
<tr>
<td colspan="4" align="right">
<input type="button" value="<?= $zmSlangSubmit ?>" class="form" onClick="if ( validateForm( document.filter_form ) ) submitToEvents( document.filter_form );">&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangExecute ?>" class="form" onClick="if ( validateForm( document.filter_form ) ) executeFilter( document.filter_form );">&nbsp;&nbsp;
<input type="button" value="<?= $zmSlangReset ?>" class="form" onClick="submitToFilter( document.filter_form, 1 );">
</td>
</tr>
</table>
</td>
</tr>
</table></center>
</form>
</body>
</html>

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
$result = mysql_query( "select * from Filters order by Name" );
if ( !$result )
	die( mysql_error() );
while ( $row = mysql_fetch_assoc( $result ) )
{
	$filter_names[$row['Name']] = $row['Name'];
	if ( $reload && isset($filter_name) && $filter_name == $row['Name'] )
	{
		$filter_data = $row;
	}
}
mysql_free_result( $result );

$auto_str = "";
if ( isset($filter_data) )
{
	$auto_text = array();
	if ( $filter_data['AutoArchive'] ) 
		$auto_text[] = $zmSlangAutoArchiveAbbr;
	if ( $filter_data['AutoVideo'] ) 
		$auto_text[] = $zmSlangAutoVideoAbbr;
	if ( $filter_data['AutoUpload'] ) 
		$auto_text[] = $zmSlangAutoUploadAbbr;
	if ( $filter_data['AutoEmail'] ) 
		$auto_text[] = $zmSlangAutoEmailAbbr;
	if ( $filter_data['AutoMessage'] ) 
		$auto_text[] = $zmSlangAutoMessageAbbr;
	if ( $filter_data['AutoExecute'] ) 
		$auto_text[] = $zmSlangAutoExecuteAbbr;
	if ( $filter_data['AutoDelete'] ) 
		$auto_text[] = $zmSlangAutoDeleteAbbr;
	if ( count($auto_text) )
	{
		$auto_str = '['.join( ',', $auto_text ).']';
	}

	foreach( split( '&', $filter_data['Query'] ) as $filter_parm )
	{
		list( $key, $value ) = split( '=', $filter_parm, 2 );
		if ( $key )
		{
			$$key = $value;
		}
	}
}

$conjunction_types = array(
	'and' => $zmSlangConjAnd,
	'or'  => $zmSlangConjOr
);
$obracket_types = array( '0' => '' );
$cbracket_types = array( '0' => '' );
for ( $i = 1; $i <= ceil(($trms-1)/2); $i++ )
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
if ( !$sort_field )
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
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
function validateForm( form )
{
<?php
if ( $trms > 2 )
{
?>
	var bracket_count = 0;
<?php
for ( $i = 1; $i <= $trms; $i++ )
{
?>
	bracket_count += parseInt(form.obr<?= $i ?>.options[form.obr<?= $i ?>.selectedIndex].value);
	bracket_count -= parseInt(form.cbr<?= $i ?>.options[form.cbr<?= $i ?>.selectedIndex].value);
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
for ( $i = 1; $i <= $trms; $i++ )
{
?>
	if ( form.val<?= $i?>.value == '' )
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
	var Url = '<?= $PHP_SELF ?>';
	var Name = 'zmEvents';
	var Width = <?= $jws['events']['w'] ?>;
	var Height = <?= $jws['events']['h'] ?>;
	var Options = 'resizable,scrollbars,width='+Width+',height='+Height;

	form.target = Name;
	form.view.value = 'events';
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
	form.action.value = 'addterm';
	form.subaction.value = line;
	form.submit();
}
function delTerm( form, line )
{
	form.target = window.name;
	form.view.value = '<?= $view ?>';
	form.action.value = 'delterm';
	form.subaction.value = line;
	form.submit();
}
window.focus();
</script>
</head>
<body>
<form name="filter_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="filter">
<input type="hidden" name="page" value="<?= $page ?>">
<input type="hidden" name="reload" value="0">
<input type="hidden" name="action" value="">
<input type="hidden" name="subaction" value="">
<input type="hidden" name="fid" value="">
<input type="hidden" name="trms" value="<?= $trms ?>">
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="text"><?= $zmSlangUseFilter ?>:&nbsp;<?php if ( count($filter_names) > 1 ) { echo buildSelect( $select_name, $filter_names, "submitToFilter( document.filter_form, 1 );" ); } else { ?><select class="form" disabled><option><?= $zmSlangNoSavedFilters ?></option></select><?php } ?>&nbsp;&nbsp;<?= $auto_str ?></td>
<?php if ( canEdit( 'Events' ) ) { ?>
<td align="right" class="text"><a href="javascript: saveFilter( document.filter_form );"><?= $zmSlangSave ?></a></td>
<?php } else { ?>
<td align="right" class="text">&nbsp;</a></td>
<?php } ?>
<?php if ( canEdit( 'Events' ) && isset($filter_data) ) { ?>
<td align="right" class="text"><a href="javascript: deleteFilter( document.filter_form, '<?= $filter_data['Name'] ?>' );"><?= $zmSlangDelete ?></a></td>
<?php } else { ?>
<td align="right" class="text">&nbsp;</a></td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr>
<td colspan="4" class="text"><hr width="100%"></td>
</tr>
<tr>
<td colspan="4">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<?php
for ( $i = 1; $i <= $trms; $i++ )
{
$conjunction_name = "cnj$i";
$obracket_name = "obr$i";
$cbracket_name = "cbr$i";
$attr_name = "attr$i";
$op_name = "op$i";
$value_name = "val$i";
?>
<tr>
<?php
if ( $i == 1 )
{
?>
<td class="text">&nbsp;</td>
<?php
}
else
{
?>
<td class="text"><?= buildSelect( $conjunction_name, $conjunction_types ); ?></td>
<?php
}
?>
<td class="text"><?php if ( $trms > 2 ) { echo buildSelect( $obracket_name, $obracket_types ); } else { ?>&nbsp;<?php } ?></td>
<td class="text"><?= buildSelect( $attr_name, $attr_types, "$value_name.value = ''; submitToFilter( document.filter_form, 0 );" ); ?></td>
<?php if ( $$attr_name == "Archived" ) { ?>
<td class="text"><center><?= $zmSlangIsEqualTo ?><input type="hidden" name="<?= $op_name ?>" value="="></center></td>
<td class="text"><?= buildSelect( $value_name, $archive_types ); ?></td>
<?php } elseif ( $$attr_name ) { ?>
<td class="text"><?= buildSelect( $op_name, $op_types ); ?></td>
<td class="text"><input name="<?= $value_name ?>" value="<?= $$value_name ?>" class="form" size="24"></td>
<?php } else { ?>
<td class="text"><?= buildSelect( $op_name, $op_types ); ?></td>
<td class="text"><input name="<?= $value_name ?>" value="<?= isset($$value_name)?$$value_name:'' ?>" class="form" size="24"></td>
<?php } ?>
<td class="text"><?php if ( $trms > 2 ) { echo buildSelect( $cbracket_name, $cbracket_types ); } else { ?>&nbsp;<?php } ?></td>
<td class="text"><a href="javascript: addTerm(document.filter_form,<?= $i ?>)">+</a><?php if ( $trms > 1 ) { ?><a href="javascript: delTerm(document.filter_form,<?= $i ?>)">&ndash;</a><?php } ?></td>
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
<td colspan="4" class="text"><table width="100%" cellpadding="0" cellspacing="0"><tr><td class="text" align="left"><?= $zmSlangSortBy ?>&nbsp;<?= buildSelect( "sort_field", $sort_fields ); ?>&nbsp;<?= buildSelect( "sort_asc", $sort_dirns ); ?></td><td class="text" align="right"><?= $zmSlangLimitResultsPre ?> <input type="input" size="6" name="limit" value="<?= $limit ?>" class="form"> <?= $zmSlangLimitResultsPost ?></td></tr></table></td>
</tr>
<tr>
<td colspan="4" class="text"><hr width="100%"></td>
</tr>
<tr>
<td colspan="4" align="right"><input type="button" value="<?= $zmSlangReset ?>" class="form" onClick="submitToFilter( document.filter_form, 1 );">&nbsp;&nbsp;<input type="button" value="<?= $zmSlangSubmit ?>" class="form" onClick="if ( validateForm( document.filter_form ) ) submitToEvents( document.filter_form, 1 );"></td>
</tr>
</table></center>
</form>
</body>
</html>

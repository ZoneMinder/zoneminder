<?php
//
// ZoneMinder web event view file, $Date$, $Revision$
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
if ( !isset($mode) )
{
    if ( ZM_WEB_USE_STREAMS && canStream() )
        $mode = "stream";
    else
        $mode = "still";
}

if ( $user['MonitorIds'] )
{
    $mid_sql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}
else
{
    $mid_sql = '';
}

$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height,M.DefaultRate,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'$mid_sql";
$result = mysql_query( $sql );
if ( !$result )
    die( mysql_error() );
$event = mysql_fetch_assoc( $result );
mysql_free_result( $result );

parseSort();
parseFilter();

$sql = "select E.* from Events as E inner join Monitors as M on E.MonitorId = M.Id where $sort_column ".($sort_order=='asc'?'<=':'>=')." '".$event[preg_replace( '/^.*\./', '', $sort_column )]."'$filter_sql$mid_sql order by $sort_column ".($sort_order=='asc'?'desc':'asc');
$result = mysql_query( $sql );
if ( !$result )
    die( mysql_error() );
while ( $row = mysql_fetch_assoc( $result ) )
{
    if ( $row['Id'] == $eid )
    {
        $prev_event = mysql_fetch_assoc( $result );
        break;
    }
}
mysql_free_result( $result );

$sql = "select E.* from Events as E inner join Monitors as M on E.MonitorId = M.Id where $sort_column ".($sort_order=='asc'?'>=':'<=')." '".$event[preg_replace( '/^.*\./', '', $sort_column )]."'$filter_sql$mid_sql order by $sort_column $sort_order";
$result = mysql_query( $sql );
if ( !$result )
    die( mysql_error() );
while ( $row = mysql_fetch_assoc( $result ) )
{
    if ( $row['Id'] == $eid )
    {
        $next_event = mysql_fetch_assoc( $result );
        break;
    }
}
mysql_free_result( $result );

if ( !isset( $rate ) )
    $rate = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );
if ( !isset( $scale ) )
    $scale = reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
if ( $mode == "still" && $scale < SCALE_BASE )
    $scale = SCALE_BASE;

$frames_per_line = ZM_WEB_FRAMES_PER_LINE;
$frames_per_page = $frames_per_line * ZM_WEB_FRAME_LINES;

$paged = $event['Frames'] > $frames_per_page;

if ( $mode == "stream" )
{
    $sql = "select max(Delta)-min(Delta) as Duration from Frames where EventId = '$eid'";
    $result = mysql_query( $sql );
    if ( !$result )
        die( mysql_error() );
    $frame_data = mysql_fetch_assoc( $result );
    mysql_free_result( $result );
    $frame_data['RealDuration'] = ($frame_data['Duration']*RATE_BASE)/$rate;

    $panel_init_color = '#eeeeee';
    $panel_done_color = '#aaaaaa';
    $panel_border_color = '#666666';
    $panel_divider_color = '#999999';

    $panel_sections = 40;
    $panel_section_width = (int)ceil(reScale($event['Width'],$scale)/$panel_sections);
    $panel_width = ($panel_sections*$panel_section_width-1);
    //$panel_section_width = 10;
    //$panel_sections = ((int)($event['Width']/$panel_section_width))+1;
    //$panel_width = $panel_sections*$panel_section_width;
    $panel_timeout = (int)((($frame_data['RealDuration']+1)*1000)/$panel_sections);

    if ( !isset( $play ) )
        $play = false;
}

ob_start();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEvent ?> - <?= $event['Name'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( !$event )
{
?>
opener.location.reload(true);
window.close();
<?php
}
?>
window.focus();
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
function refreshWindow()
{
    window.location.reload(true);
}
function closeWindow()
{
    window.close();
}
function newWindow(Url,Name,Width,Height)
{
    var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
<?php
if ( $mode == "stream" && ZM_WEB_SHOW_PROGRESS && isNetscape() )
{
?>
function incrementPanel( section )
{
    document.getElementById( 'PanelSection'+section ).style.backgroundColor = '<?= $panel_done_color ?>';
    section++;
    if ( section < <?= $panel_sections ?> )
    {
        window.setTimeout( "incrementPanel( "+section+" )", <?= $panel_timeout ?> );
    }
}
<?php
}
?>
</script>
<?php
if ( $mode == "stream" )
{
?>
<style type="text/css">
<!--
#Panel {
    position: relative;
    text-align: center;
    border: 1px solid <?= $panel_border_color ?>;
    height: 15px;
    width: <?= $panel_width ?>px;
    padding: 0px;
    margin: auto;
}

#Panel div.Section {
    position: absolute;
    height: 15px;
}
-->
</style>
<?php
}
?>
</head>
<body scroll="auto">
<table border="0" cellspacing="0" cellpadding="3" width="100%">
<tr>
<td><table border="0" cellspacing="1" cellpadding="2" width="100%" style="background: #666699">
<tr style="background: #ffffff">
<td class="text" align="center"><span title="<?= $zmSlangId ?>"><?= $event['Id'] ?></span></td>
<td class="text" align="center"><span title="<?= htmlentities($event['Notes']) ?>"><?= htmlentities($event['Cause']) ?></span></td>
<td class="text" align="center"><span title="<?= $zmSlangTime ?>"><?= strftime( STRF_FMT_DATETIME_SHORT, strtotime($event['StartTime'] ) ) ?></span></td>
<td class="text" align="center"><span title="<?= $zmSlangDuration ?>"><?= $event['Length']."s" ?></span></td>
<td class="text" align="center"><span title="<?= $zmSlangAttrFrames."/".$zmSlangAttrAlarmFrames ?>"><?= $event['Frames'] ?>/<?= $event['AlarmFrames'] ?></span></td>
<td class="text" align="center"><span title="<?= $zmSlangAttrTotalScore."/".$zmSlangAttrAvgScore."/".$zmSlangAttrMaxScore ?>"><?= $event['TotScore'] ?>/<?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></span></td>
</tr>
</table></td>
</tr>
<tr>
<td><table border="0" cellspacing="0" cellpadding="2" width="100%">
<tr>
<td align="left" class="text"><form name="rename_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="mode" value="<?= $mode ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<?= $filter_fields ?>
<input type="hidden" name="sort_field" value="<?= $sort_field ?>">
<input type="hidden" name="sort_asc" value="<?= $sort_asc ?>">
<input type="hidden" name="limit" value="<?= $limit ?>">
<input type="hidden" name="rate" value="<?= $rate ?>">
<input type="hidden" name="scale" value="<?= $scale ?>">
<input type="hidden" name="page" value="<?= $page ?>">
<input type="text" size="16" name="event_name" value="<?= $event['Name'] ?>" class="form">
<input type="submit" value="<?= $zmSlangRename ?>" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></form></td>
<?php if ( 0 ) { ?>
<td align="center" class="text"><form name="learn_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="learn">
<input type="hidden" name="mode" value="<?= $mode ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="hidden" name="mark_eid" value="<?= $eid ?>">
<?php if ( LEARN_MODE ) { ?>
Learn Pref:&nbsp;<select name="learn_state" class="form" onChange="learn_form.submit();"><option value=""<?php if ( !$event['LearnState'] ) echo " selected" ?>><?= $zmSlangIgnore ?></option><option value="-"<?php if ( $event['LearnState']=='-' ) echo " selected" ?>><?= $zmSlangExclude ?></option><option value="+"<?php if ( $event['LearnState']=='+' ) echo " selected" ?>><?= $zmSlangInclude ?></option></select>
<?php } ?>
</form></td>
<?php } ?>
<td align="right" class="text"><form name="view_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="mode" value="<?= $mode ?>">
<input type="hidden" name="page" value="<?= $page ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<?= $filter_fields ?>
<input type="hidden" name="sort_field" value="<?= $sort_field ?>">
<input type="hidden" name="sort_asc" value="<?= $sort_asc ?>">
<input type="hidden" name="limit" value="<?= $limit ?>">
<?= $zmSlangRate ?>: <?= buildSelect( "rate", $rates, "document.view_form.submit();" ); ?>&nbsp;&nbsp;
<?= $zmSlangScale ?>: <?= buildSelect( "scale", $scales, "document.view_form.submit();" ); ?>
</form></td>
</tr>
</table></td></tr>
<tr>
<td><table border="0" cellspacing="0" cellpadding="2" width="100%">
<tr>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="javascript: refreshWindow();"><?= $zmSlangReplay ?></a></td>
<?php } elseif ( $paged && !empty($page) ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=0"><?= $zmSlangAll ?></a></td>
<?php } elseif ( $paged && empty($page) ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=1"><?= $zmSlangPaged ?></a></td>
<?php } ?>
<?php if ( canEdit( 'Events' ) ) { ?><td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=none&action=delete&mark_eid=<?= $eid ?>"><?= $zmSlangDelete ?></a></td><?php } ?>
<?php if ( canEdit( 'Events' ) ) { ?><td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=eventdetail&eid=<?= $eid ?>', 'zmEventDetail', <?= $jws['eventdetail']['w'] ?>, <?= $jws['eventdetail']['h'] ?> )"><?= $zmSlangEdit ?></a></td><?php } ?>
<?php if ( canEdit( 'Events' ) ) { ?><td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=export&eid=<?= $eid ?>', 'zmExport', <?= $jws['export']['w'] ?>, <?= $jws['export']['h'] ?> )"><?= $zmSlangExport ?></a></td><?php } ?>
<?php if ( canEdit( 'Events' ) ) { ?>
<?php if ( $event['Archived'] ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=unarchive&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>"><?= $zmSlangUnarchive ?></a></td>
<?php } else { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=archive&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>"><?= $zmSlangArchive ?></a></td>
<?php } ?>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=1"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=stream&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>"><?= $zmSlangStream ?></a></td>
<?php } ?>
<?php if ( ZM_OPT_MPEG != "no" ) { ?>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=video&eid=<?= $eid ?>', 'zmVideo', <?= $jws['video']['w']+$event['Width'] ?>, <?= $jws['video']['h']+$event['Height'] ?> );"><?= $zmSlangVideo ?></a></td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
</table></td></tr>
<?php
if ( $mode == "still" && $paged && !empty($page) )
{
?>
<?php
    $pages = (int)ceil($event['Frames']/$frames_per_page);
    $max_shortcuts = 5;
?>
<tr><td align="center" class="text">
<?php
    if ( $page < 0 )
        $page = 1;
    if ( $page > $pages )
        $page = $pages;

    if ( $page > 1 )
    {
        if ( false && $page > 2 )
        {
?>
<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=1">&lt;&lt;</a>&nbsp;
<?php
        }
?>
<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $page - 1 ?>">&lt;</a>&nbsp;
<?php
        $new_pages = array();
        $pages_used = array();
        $lo_exp = max(2,log($page-1)/log($max_shortcuts));
        for ( $i = 0; $i < $max_shortcuts; $i++ )
        {
            $new_page = round($page-pow($lo_exp,$i));
            if ( isset($pages_used[$new_page]) )
                continue;
            if ( $new_page <= 1 )
                break;
            $pages_used[$new_page] = true;
            array_unshift( $new_pages, $new_page );
        }
        if ( !isset($pages_used[1]) )
            array_unshift( $new_pages, 1 );

        foreach ( $new_pages as $new_page )
        {
?>
<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $new_page ?>"><?= $new_page ?></a>&nbsp;
<?php
        }
    }
?>
-&nbsp;<?= $page ?>&nbsp;-
<?php
    if ( $page < $pages )
    {
        $new_pages = array();
        $pages_used = array();
        $hi_exp = max(2,log($pages-$page)/log($max_shortcuts));
        for ( $i = 0; $i < $max_shortcuts; $i++ )
        {
            $new_page = round($page+pow($hi_exp,$i));
            if ( isset($pages_used[$new_page]) )
                continue;
            if ( $new_page > $pages )
                break;
            $pages_used[$new_page] = true;
            array_push( $new_pages, $new_page );
        }
        if ( !isset($pages_used[$pages]) )
            array_push( $new_pages, $pages );

        foreach ( $new_pages as $new_page )
        {
?>
&nbsp;<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $new_page ?>"><?= $new_page ?></a>
<?php
        }
?>
&nbsp;<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $page + 1 ?>">&gt;</a>
<?php
        if ( false && $page < ($pages-1) )
        {
?>
&nbsp;<a href="<?= $PHP_SELF ?>?view=event&mode=still&eid=<?= $eid ?>&scale=<?= $scale ?><?= $filter_query ?><?= $sort_query ?>&page=<?= $pages ?>">&gt;&gt;</a>
<?php
        }
    }
?>
</td></tr>
<?php
}
?>
<?php
if ( $mode == "stream" )
{
?>
<tr><td align="center" valign="middle">
<?php
    if ( ZM_STREAM_METHOD == 'mpeg' && ZM_MPEG_REPLAY_FORMAT )
    {
        $stream_src = getStreamSrc( array( "mode=mpeg", "event=".$eid, "frame=".(!empty($fid)?$fid:1), "scale=".$scale, "rate=".$rate, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_REPLAY_FORMAT ) );
        outputVideoStream( $stream_src, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), $event['Name'], ZM_MPEG_REPLAY_FORMAT );
    }
    else
    {
        $stream_src = getStreamSrc( array( "mode=jpeg", "event=".$eid, "frame=".(!empty($fid)?$fid:1), "scale=".$scale, "rate=".$rate, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
        if ( canStreamNative() )
        {
            outputImageStream( $stream_src, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), $event['Name'] );
        }
        else
        {
            outputHelperStream( $stream_src, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) );
        }
    }
?>
</td></tr>
<?php
    if ( isNetscape() )
    {
?>
<tr>
<td><div id="Panel">
<?php
        for ( $i = 0; $i < $panel_sections; $i++ )
        {
            $start_frame = 1+(int)round(($i * $event['Frames'])/$panel_sections);
            if ( ZM_WEB_SHOW_PROGRESS && !empty($fid) && $start_frame < $fid )
            {
                $section_color = $panel_done_color;
            }
            else
            {
                $section_color = $panel_init_color;
            }
            $section_width = $panel_section_width;
            if ($i == 0 || $i == ($panel_sections-1) )
                $section_width--;
            if ( $i )
            {
                $section_offset = ($i * $panel_section_width)-1;
                $divider = " border-left: solid 1px $panel_divider_color;";
            }
            else
            {
                $section_offset = 0;
                $divider = "";
            }
            $title = "+".(int)round(($i * $frame_data['Duration'])/$panel_sections)."s";
?>
<div class="Section" id="PanelSection<?= $i ?>" title="<?= $title ?>" style="width: <?= $section_width ?>px; left: <?= $section_offset ?>px; background-color: <?= $section_color ?>;<?= $divider ?>" onClick="window.location='<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $event['Id'] ?>&fid=<?= $start_frame ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>'"></div>
<?php
        }
?>
</div></td>
</tr>
<?php
    }
}
else
{
    if ( $paged && !empty($page) )
    {
        $lo_frame_id = (($page-1)*$frames_per_page);
        $hi_frame_id = min( $page*$frames_per_page, $event['Frames'] );
    }
    else
    {
        $lo_frame_id = 0;
        $hi_frame_id = $event['Frames'];
    }
    $sql = "select * from Frames where EventId = '$eid'";
    $sql .= " order by FrameId";
    if ( $paged && !empty($page) )
        $sql .= " limit $lo_frame_id, $hi_frame_id";
    $result = mysql_query( $sql );
    if ( !$result )
        die( mysql_error() );
    $frames = array();
    while( $frame = mysql_fetch_assoc( $result ) )
    {
        $frames[] = $frame;
    }
    mysql_free_result( $result );
?>
<tr><td><div style="text-align: center">
<?php
    $count = 0;

    $thumb_width = (int)round($event['Width']/ZM_WEB_FRAMES_PER_LINE);
    $thumb_height = (int)round($event['Height']/ZM_WEB_FRAMES_PER_LINE);
    $thumb_scale = (int)round( SCALE_BASE/ZM_WEB_FRAMES_PER_LINE );

    for ( $i = 0; $i < count($frames); $i++ )
    {
        $frame = $frames[$i];
        $image_data = getImageSrc( $event, $frame, $thumb_scale );
?>
<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $frame['FrameId'] ?>&scale=<?= $scale ?>', 'zmImage', <?= reScale( $event['Width'], $scale )+$jws['image']['w']  ?>, <?= reScale( $event['Height'], $scale )+$jws['image']['h'] ?> );"><img src="<?= $image_data['thumbPath'] ?>" width="<?= $thumb_width ?>" height="<?= $thumb_height ?>" class="<?= $image_data['imageClass'] ?>" alt="<?= $frame['FrameId'] ?>/<?= $frame['Type']=='alarm'?$frame['Score']:0 ?>"></a>
<?php
    }
?>
</div></td></tr>
<?php
}
?>
<tr>
<td><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="20%" align="center" class="text"><?php if ( $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $prev_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangPrev ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="20%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $prev_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>&action=delete&mark_eid=<?= $eid ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangDeleteAndPrev ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="20%" align="center" class="text"><?php if ( $mode == "stream" ) { if ( $play && $next_event ) { ?><a href="javascript: window.clearTimeout( timeout_id );"><?= $zmSlangStop ?></a><?php } elseif ( $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $eid ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>&play=1"><?= $zmSlangPlayAll ?></a><?php } else { ?>&nbsp;<?php } } else { ?>&nbsp;<?php } ?></td>
<td width="20%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $next_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>&action=delete&mark_eid=<?= $eid ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangDeleteAndNext ?></a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="20%" align="center" class="text"><?php if ( $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $next_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>"><?= $zmSlangNext ?></a><?php } else { ?>&nbsp;<?php } ?></td>
</tr></table></td>
</tr>
</table>
<?php
if ( $mode == "stream" )
{
?>
<script type="text/javascript">
<?php
    if ( $play && $next_event )
    {
?>
var timeout_id = window.setTimeout( "window.location.replace( '<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&eid=<?= $next_event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>&page=<?= $page ?>&rate=<?= $rate ?>&scale=<?= $scale ?>&play=1' );", <?= ($frame_data['RealDuration']+1)*1000 ?> );
<?php
    }
    $start_section = 0;
    if ( !empty($fid) )
    {
        $start_section = (int)floor((($fid-1) * $panel_sections)/($event['Frames']+1));
    }
    if ( ZM_WEB_SHOW_PROGRESS && isNetscape() )
    {
?>
window.setTimeout( "incrementPanel( <?= $start_section ?> )", <?= $panel_timeout ?> );
<?php
    }
?>
</script>
<?php
}
?>
</body>
</html>
<?php
ob_end_flush();
?>

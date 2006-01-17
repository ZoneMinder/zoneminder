<?php
//
// ZoneMinder web feed file, $Date$, $Revision$
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

$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );
mysql_free_result( $result );
$browser = array();
$browser['Width'] = 100;
$browser['Height'] = 80;

// Generate an image
chdir( ZM_DIR_IMAGES );
$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -i" ) );
$monitor_image = $monitor['Name'].".jpg";
$image_time = filemtime( $monitor_image );
$browser_image = $monitor['Name']."-wap-$image_time.jpg";
$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $monitor_image | ".ZM_PATH_NETPBM."/pnmscale -xysize ".$browser['Width']." ".$browser['Height']." | ".ZM_PATH_NETPBM."/ppmtojpeg > $browser_image";
exec( $command );
chdir( '..' );

?>
<wml>
<card id="zmFeed" title="<?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?>" ontimer="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;mid=<?= $mid ?>">
<timer value="<?= ZM_WEB_REFRESH_IMAGE*10 ?>"/>
<p mode="nowrap" align="center"><strong><?= $monitor['Name'] ?></strong></p>
<p mode="nowrap" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$browser_image ?>" alt="<?= $monitor['Name'] ?>" hspace="0" vspace="0" align="middle"/></p>
</card>
</wml>
<?php
flush();
?>

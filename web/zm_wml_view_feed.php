<?php
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
	$browser = array();
	$browser[Width] = 100;
	$browser[Height] = 80;

	// Generate an image
	chdir( ZM_DIR_IMAGES );
	$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -i" ) );
	$monitor_image = "$monitor[Name].jpg";
	$image_time = filemtime( $monitor_image );
	$browser_image = "$monitor[Name]-wap-$image_time.jpg";
	$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $monitor_image | ".ZM_PATH_NETPBM."/pnmscale -xysize $browser[Width] $browser[Height] | ".ZM_PATH_NETPBM."/ppmtojpeg > $browser_image";
	exec( $command );
	chdir( '..' );
?>
<wml>
<card id="zmFeed" title="ZM - <?= $monitor[Name] ?>" ontimer="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;mid=<?= $mid ?>">
<timer value="<?= REFRESH_IMAGE*10 ?>"/>
<p mode="nowrap" align="center"><strong>ZM - <?= $monitor[Name] ?></strong></p>
<p mode="nowrap" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$browser_image ?>" alt="<?= $monitor[Name] ?>" hspace="0" vspace="0" align="middle"/></p>
</card>
</wml>
<?php
	flush();
?>

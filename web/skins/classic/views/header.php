<?php

require_once('includes/Server.php');

$running = daemonCheck();
$status = $running?translate('Running'):translate('Stopped');
$run_state = dbFetchOne('select Name from States where  IsActive = 1', 'Name' );

$group = NULL;
if ( ! empty($_COOKIE['zmGroup']) ) {
	if ( $group = dbFetchOne( 'select * from Groups where Id = ?', NULL, array($_COOKIE['zmGroup'])) )
		$groupIds = array_flip(explode( ',', $group['MonitorIds'] ));
}


$versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'errorText':'';


?>
<div id="navHeader" class="navbar-fixed-top">
<div class="navbar navbar-inverse">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-header-nav" aria-expanded="false">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a>
		</div>

		<div class="collapse navbar-collapse" id="main-header-nav">
		<ul class="nav navbar-nav">
			<li><a href="?view=console"><?php echo translate('Console') ?></a></li>
<?php if ( canView( 'System' ) ) { ?>
			<li><a href="?view=options"><?php echo translate('Options') ?></a></li>
			<?php if ( logToDatabase() > Logger::NOLOG ) { ?>
			<li><a href="?view=log"><span class="<?php echo logState() ?>"><?php echo translate('Log')?></span></a></li>
			<?php } ?>
<?php } ?>
<?php if ( ZM_OPT_X10 && canView( 'Devices' ) ) { ?>
			<li><a href="/?view=devices">Devices</a></li>
<?php } ?>
			<li><a href="?view=groups">Groups</a></li>
			<li><?php echo makePopupLink( '?view=filter&amp;filter[terms][0][attr]=DateTime&amp;filter[terms][0][op]=%3c&amp;filter[terms][0][val]=now', 'zmFilter', 'filter', translate('Filters'), canView( 'Events' ) ) ?></li>

<?php if ( canView( 'Stream' ) ) {
	$cycleGroup = isset($_COOKIE['zmGroup'])?$_COOKIE['zmGroup']:0;
?>
			<li><a href="?view=montage"><?php echo translate('Montage') ?></a> </li>
<?php } ?>
		</ul>

<div class="navbar-right">
<?php if ( ZM_OPT_USE_AUTH ) { ?>
	<p class="navbar-text"><?php echo translate('LoggedInAs') ?> <?php echo makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?> </p>
<?php } ?>

<?php if ( canEdit( 'System' ) ) { ?>
	<p class="navbar-text"><?php echo makePopupLink( '?view=state', 'zmState', 'state', $status, canEdit( 'System' ) ) ?> - <?php echo $run_state ?></p>

<?php } else if ( canView( 'System' ) ) { ?>
		<p class="navbar-text"> <?php echo $status ?> </p>
<?php } ?>
</div>



		</div><!-- End .navbar-collapse -->
	</div> <!-- End .container-fluid -->
</div> <!-- End .navbar .navbar-default -->

<nav class="navbar navbar-inverse">
	<div class="container-fluid">
		<ul class="nav navbar-nav navbar-left">
			<li><?php echo makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', $bwArray[$_COOKIE['zmBandwidth']] . ' Bandwidth', ($user && $user['MaxBandwidth'] != 'low' ) ) ?></li>
		</ul>
		<ul class="nav navbar-nav navbar-right">
			<li><?php echo makePopupLink( '?view=version', 'zmVersion', 'version', 'v'.ZM_VERSION, canEdit( 'System' ) ) ?></li>
			<li><a href="#"><?php echo translate('Load') ?>: <?php echo getLoad() ?></a></li>
			<li><a href="#"><?php echo translate('Disk') ?>: <?php echo getDiskPercent() ?>%</a></li>
		</ul>
	</div>
</nav>

</div> <!-- End header container -->

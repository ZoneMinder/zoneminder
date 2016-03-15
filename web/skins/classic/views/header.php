<div class="navbar navbar-default">
	<div class="container">
		<div class="navbar-header">
			<a class="navbar-brand" href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a>
		</div>

		<ul class="nav navbar-nav">
<?php if ( canView( 'System' ) ) { ?>
			<li><?php echo makePopupLink( '?view=options', 'zmOptions', 'options', translate('Options') ) ?></li>
			<li><?php if ( logToDatabase() > Logger::NOLOG ) { ?> <?php echo makePopupLink( '?view=log', 'zmLog', 'log', '<span class="'.logState().'">'.translate('Log').'</span>' ) ?><?php } ?></li>
<?php } ?>
<?php if ( ZM_OPT_X10 && canView( 'Devices' ) ) { ?>
			<li><a href="/?view=devices">Devices</a></li>
<?php } ?>
			<li><?php echo makePopupLink( '?view=groups', 'zmGroups', 'groups', sprintf( $CLANG['MonitorCount'], count($displayMonitors), zmVlang( $VLANG['Monitor'], count($displayMonitors) ) ).($group?' ('.$group['Name'].')':''), canView( 'Groups' ) ); ?></li>
			<li><?php echo makePopupLink( '?view=filter&amp;filter[terms][0][attr]=DateTime&amp;filter[terms][0][op]=%3c&amp;filter[terms][0][val]=now', 'zmFilter', 'filter', translate('Filters'), canView( 'Events' ) ) ?></li>

<?php if ( canView( 'Stream' ) && $cycleCount > 1 ) {
	$cycleGroup = isset($_COOKIE['zmGroup'])?$_COOKIE['zmGroup']:0;
?>
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown">Montage <span class="caret"></span></a>
				<ul class="dropdown-menu">
					<li><?php echo makePopupLink( '?view=cycle&amp;group='.$cycleGroup, 'zmCycle'.$cycleGroup, array( 'cycle', $cycleWidth, $cycleHeight ), translate('Cycle'), $running ) ?></li>
					<li><?php echo makePopupLink( '?view=montage&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montage', translate('Montage'), $running ) ?></li>
					<li><?php echo makePopupLink( '?view=montagereview&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montagereview', translate('Montage Review'), $running ) ?></li>
				</ul>
			</li>
<?php } ?>
		</ul>

<div class="navbar-right">
<?php if ( ZM_OPT_USE_AUTH ) { ?>
	<p class="navbar-text"><?php echo translate('LoggedInAs') ?> <?php echo makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?> </p>
<?php } ?>

<?php if ( canEdit( 'System' ) ) { ?>
		<a class="btn btn-default navbar-btn" href="/?view=state" onclick="createPopup( '?view=state', 'zmState', 'state' ); return( false );"> <?php echo $status ?> </a>

<?php } else if ( canView( 'System' ) ) { ?>
		<p class="navbar-text"> <?php echo $status ?> </p>
<?php } ?>
</div>

	</div> <!-- End .container-fluid -->
</div> <!-- End .navbar .navbar-default -->

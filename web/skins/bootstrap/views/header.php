<?php
	$running = daemonCheck();
	$status = $running?$SLANG['Running']:$SLANG['Stopped'];
	if ($status == 'Running') {
		$statusClass = 'success';
	} else {
	  $statusClass = 'danger';
	}
?>

    <nav class="navbar navbar-default" role="navigation">
			<div class="container-fluid">
				<span class="navbar-brand">ZoneMinder</span>

				<button type="button" class="btn btn-<?= $statusClass ?> btn-md navbar-btn pull-right" data-toggle="modal" data-target="#myModal">
					<span class="glyphicon glyphicon-off"></span>
				</button>

				<ul class="nav navbar-nav pull-right">
					<li><a href="?view=console"><?= $SLANG['Console']; ?></a></li>
					<li><a href="?view=events&amp;page=1"><?= $SLANG['Events']; ?></a></li>
					<li><a href="?view=timeline"><?= $SLANG['Timeline']; ?></a></li>
      		<li><a href="?view=options"><?= $SLANG['Options'] ?></a></li>
					<?php if ( logToDatabase() > Logger::NOLOG ) { ?>
						<li><a href="?view=log"><span class="<?= logState(); ?>"><?= $SLANG['Log'] ?></span></a></li>
					<?php } ?>
      		<li><a href="?view=groups"><?= sprintf( $CLANG['MonitorCount'], count($displayMonitors), zmVlang( $VLANG['Monitor'], count($displayMonitors) ) ).($group?' ('.$group['Name'].')':'')?></a></li>
					<?php if ( ZM_OPT_X10 && canView( 'Devices' ) ) { ?>
						<li><a href="?view=devices"><?= $SLANG['Devices'] ?></a></li>
					<?php } ?>
					<?php if ($running) ?>
						<li><a href="?view=cycle&amp;group=<?= $cycleGroup ?>"><?= $SLANG['Cycle'] ?></a></li>
						<li><a href="?view=montage&amp;group=<?= $cycleGroup ?>"><?= $SLANG['Montage'] ?></a></li>
					<?php ?>
				</ul>
			</div>
    </nav>
<?php include("state.php"); ?>

<?php
	$running = daemonCheck();
	$status = $running?$SLANG['Running']:$SLANG['Stopped'];
	if ($status == 'Running') {
		$statusClass = 'success';
	} else {
	  $statusClass = 'danger';
	}
?>

    <nav class="navbar navbar-default" role="navigation" ng-controller="HeaderController">
			<div class="container-fluid">
				<a href="?view=console"><span class="navbar-brand">ZoneMinder</span></a>

				<button type="button" class="btn btn-md navbar-btn pull-right" ng-class="isRunning ? 'btn-success' : 'btn-danger'" data-toggle="modal" data-target="#myModal">
					<span class="glyphicon glyphicon-off"></span>
				</button>

				<ul class="nav navbar-nav pull-right">
					<li><a href="?view=events&amp;page=1"><?= $SLANG['Events']; ?></a></li>
					<li><a href="?view=timeline"><?= $SLANG['Timeline']; ?></a></li>
      		<li><a href="?view=options"><?= $SLANG['Options'] ?></a></li>
					<?php if ( logToDatabase() > Logger::NOLOG ) { ?>
						<li><a href="?view=log"><span class="<?= logState(); ?>"><?= $SLANG['Log'] ?></span></a></li>
					<?php } if ( ZM_OPT_X10 && canView( 'Devices' ) ) { ?>
						<li><a href="?view=devices"><?= $SLANG['Devices'] ?></a></li>
					<?php } ?>
					<?php if ($running) ?>
						<li><a href="?view=cycle&amp;group=<?= $cycleGroup ?>"><?= $SLANG['Cycle'] ?></a></li>
						<li><a href="?view=montage&amp;group=<?= $cycleGroup ?>"><?= $SLANG['Montage'] ?></a></li>
						<li><a href="?view=host">Host</a></li>
					<?php ?>
				</ul>
			</div>
    </nav>
<?php include("state.php"); ?>

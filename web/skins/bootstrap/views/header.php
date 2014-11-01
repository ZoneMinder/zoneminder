    <nav class="navbar navbar-default" role="navigation">
			<div class="container-fluid">
				<a href="?view=state" class="btn btn-<?= $statusClass ?> btn-md navbar-btn pull-right" onclick="createPopup( '?view=state', 'zmState', 'state' ); return( false );">
					<span class="glyphicon glyphicon-off"></span>
				</a>
				<ul class="nav navbar-nav pull-right">
      		<li>
						<?= makePopupLink( '?view=options', 'zmOptions', 'options', $SLANG['Options'] ) ?>
					</li>
					<?php if ( logToDatabase() > Logger::NOLOG ) { ?>
						<li>
							<?= makePopupLink( '?view=log', 'zmLog', 'log', '<span class="'.logState().'">'.$SLANG['Log'].'</span>' ) ?>
						</li>
					<?php } ?>
      		<li>
						<?= makePopupLink( '?view=groups', 'zmGroups', 'groups', sprintf( $CLANG['MonitorCount'], count($displayMonitors), zmVlang( $VLANG['Monitor'], count($displayMonitors) ) ).($group?' ('.$group['Name'].')':''), canView( 'System' ) ); ?>
					</li>
					<?php if ( ZM_OPT_X10 && canView( 'Devices' ) ) { ?>
						<li><?= makePopupLink( '?view=devices', 'zmDevices', 'devices', $SLANG['Devices'] ) ?></li>
					<?php } ?>
      		<li><?= makePopupLink( '?view=cycle&amp;group='.$cycleGroup, 'zmCycle'.$cycleGroup, array( 'cycle', $cycleWidth, $cycleHeight ), $SLANG['Cycle'], $running ) ?></li>
					<li><?= makePopupLink( '?view=montage&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montage', $SLANG['Montage'], $running ) ?></li>
				</ul>
			</div>
    </nav>

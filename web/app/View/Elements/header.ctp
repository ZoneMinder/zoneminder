	<div id="header" class="navbar navbar-default" role="navigation">
		<p class="navbar-text navbar-right"><?php echo $daemonStatusHtml; ?></p>
		<p class="navbar-text navbar-right">Used Event Storage: <?php echo $diskSpace; ?>%</p>
		<p class="navbar-text navbar-right">CPU Load: <?php echo $systemLoad; ?></p>
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="#">ZoneMinder</a>
			</div>
			<div class="navbar-collapse collapse">
				<?php echo $this->element('navigation'); ?>
			</div>
		</div>
	</div>

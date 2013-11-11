<?php foreach ($eventreport as $monitorname => $report): ?>
	<div class="list-group eventreport">
	<header class="list-group-item-heading"><?php echo $monitorname; ?></header>
	<?php foreach ($report as $interval => $count): ?>
		<div class="list-group-item">
			<span class="badge"><?php echo $count; ?></span>
			<p><?php echo $interval; ?></p>
		</div>
	<?php endforeach; ?>
	</div>
<?php endforeach; ?>

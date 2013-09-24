<?php
	$componentoptions = array();
	foreach ($components as $component) {
		$componentoptions[$component['Log']['Component']] = $component['Log']['Component'];
	}

	$this->start('sidebar');
	echo $this->Form->create('Logs', array(
		'default' => false,
		'role' => 'form'
	));
?>
<div class="form-group">
<?php
	echo $this->Form->label('Component');
	echo $this->Form->select('Component', $componentoptions, array('class' => 'form-control'));
?>
</div>
<?php
	$optionsend = array(
		'label' => 'Refresh',
		'div' => false,
		'class' => array('btn', 'btn-default'),
		'id' => 'btnComponentRefresh'
	);
	echo $this->Form->end($optionsend);
	$this->end();
?>

<table id="tblComponents" class="table table-condensed table-hover table-striped">
	<tr>
		<th>Date / Time</th>
		<th>Component</th>
		<th>PID</th>
		<th>Level</th>
		<th>Message</th>
		<th>File</th>
		<th>Line</th>
	</tr>
<?php
foreach ($loglines as $logline) {
	echo "<tr>";
	echo '<td>' . date('r', $logline['Log']['TimeKey']) . '</td>';
	printf("<td>%s</td>", $logline['Log']['Component']);
	printf("<td>%d</td>", $logline['Log']['Pid']);
	printf("<td>%d</td>", $logline['Log']['Level']);
	printf("<td>%s</td>", $logline['Log']['Message']);
	printf("<td>%s</td>", $logline['Log']['File']);
	printf("<td>%d</td>", $logline['Log']['Line']);
	echo "</tr>";
}
?>
</table>

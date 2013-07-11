<?php
$componentoptions = array();
foreach ($components as $component) {
	$componentoptions[$component['Log']['Component']] = $component['Log']['Component'];
}
?>
<div>
	<?php echo $this->Form->label('Component'); ?>
	<?php echo $this->Form->select('Component', $componentoptions); ?>
	<?php echo $this->Form->button('Refresh', array('id' => 'btnComponentRefresh')); ?>
</div>
<table id="tblComponents">
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

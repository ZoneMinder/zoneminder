<h2>Monitors</h2>
<table>
    <tr>
        <th>Name</th>
	<th>Function</th>
	<th>Source</th>
	<th>Total Events</th>
	<th>Hour</th>
	<th>Day</th>
	<th>Week</th>
	<th>Month</th>
	<th>Archived</th>
	<th>Zones</th>
    </tr>

    <?php foreach ($monitors as $monitor): ?>
    <tr>
        <td>
            <?php echo $this->Html->link($monitor['Monitor']['Name'],array('controller' => 'monitors', 'action' => 'view', $monitor['Monitor']['Id'])); ?>
	</td>
        <td>
            <?php echo $this->Html->link($monitor['Monitor']['Function'], array('action' => 'edit', $monitor['Monitor']['Id'])); ?>
	</td>
        <td><?php echo $monitor['Monitor']['Host']; ?></td>
	<td><?php echo count($monitor['Event']); ?></td>
	<td></th>
	<td></th>
	<td></th>
	<td></th>
	<td></th>
	<td></th>
    </tr>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
</table>

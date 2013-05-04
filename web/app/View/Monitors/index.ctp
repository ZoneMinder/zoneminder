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
<?php $count = 0; ?>
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
	<td><?php echo $eventsLastHour[$count][0]['count']; ?></td>
	<td><?php echo $eventsLastDay[$count][0]['count']; ?></td>
	<td><?php echo $eventsLastWeek[$count][0]['count']; ?></td>
	<td><?php echo $eventsLastMonth[$count][0]['count']; ?></td>
	<td></td>
	<td><?php echo count($monitor['Zone']); ?></td>
    </tr>
    <?php $count++; ?>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
</table>

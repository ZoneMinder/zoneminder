<h2>Events</h2>
<table>
    <tr>
        <th>Name</th>
	<th>Total Events</th>
	<th>Hour</th>
	<th>Day</th>
	<th>Week</th>
	<th>Month</th>
	<th>Archived</th>
    </tr>
<?php $count = 0; ?>
<?php foreach ($monitors as $monitor): ?>
	<tr>
	<td><?php echo $monitor['Monitor']['Name']; ?></td>
	<td><?php echo count($monitor['Event']); ?></td>
	<td><?php echo $eventsLastHour[$count][0]['count']; ?></td>
	<td><?php echo $eventsLastDay[$count][0]['count']; ?></td>
	<td><?php echo $eventsLastWeek[$count][0]['count']; ?></td>
	<td><?php echo $eventsLastMonth[$count][0]['count']; ?></td>
	<td><?php echo $eventsArchived[$count][0]['count']; ?></td>
	</tr>
    <?php $count++; ?>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
</table>
<?php echo $this->Paginator->numbers(); ?>
<table>
    <tr>
        <th>Event Name</th>
        <th>Monitor Name</th>
        <th>Length</th>
    </tr>

    <?php foreach ($events as $event): ?>
    <tr>
        <td>
            <?php echo $this->Html->link($event['Event']['Name'],
array('controller' => 'events', 'action' => 'view', $event['Event']['Id'])); ?>
        <td><?php echo $event['Monitor']['Name']; ?></td>
        <td><?php echo $event['Event']['Length']; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($event); ?>
</table>
<?php echo $this->Paginator->numbers(); ?>

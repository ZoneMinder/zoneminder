<h2>Events</h2>
<table>
    <tr>
        <th>Id</th>
        <th>MonitorId</th>
        <th>Length</th>
    </tr>

    <?php foreach ($events as $event): ?>
    <tr>
        <td>
            <?php echo $this->Html->link($event['Event']['Name'],
array('controller' => 'events', 'action' => 'view', $event['Event']['Id'])); ?>
        <td><?php echo $event['Event']['MonitorId']; ?></td>
        <td><?php echo $event['Event']['Length']; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($event); ?>
<?php echo $this->Paginator->numbers(); ?>
</table>

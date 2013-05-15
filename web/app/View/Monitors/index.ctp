<h2>Monitors</h2>
<table>
    <tr>
        <th>Name</th>
	<th>Function</th>
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
	<td><?php echo count($monitor['Zone']); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
	</table>
	<?php echo $this->Html->link(
		'Add Monitor',
		array('controller' => 'monitors', 'action' => 'add')
	); ?>

</table>

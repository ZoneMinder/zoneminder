<h2>Monitors</h2>
<table>
    <tr>
        <th>Name</th>
	<th>Function</th>
	<th>Source</th>
    </tr>

    <?php foreach ($monitors as $monitor): ?>
    <tr>
        <td>
            <?php echo $this->Html->link($monitor['Monitor']['Name'],
array('controller' => 'monitors', 'action' => 'view', $monitor['Monitor']['Id'])); ?>
        <td><?php echo $monitor['Monitor']['Function']; ?></td>
        <td><?php echo $monitor['Monitor']['Host']; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
</table>

<h2>Monitors</h2>
<table>
    <tr>
        <th>Name</th>
        <th>MonitorId</th>
    </tr>

    <?php foreach ($monitors as $monitor): ?>
    <tr>
        <td>
            <?php echo $this->Html->link($monitor['Monitor']['Name'],
array('controller' => 'monitors', 'action' => 'view', $monitor['Monitor']['Id'])); ?>
        <td><?php echo $monitor['Monitor']['Id']; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
</table>

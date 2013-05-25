<h2>Monitors</h2>
    <?php foreach ($monitors as $monitor => $mon): ?>
      <div class="monitor">
        <?php echo $this->Html->link($mon['Monitor']['Name'],array('controller' => 'monitors', 'action' => 'view', $mon['Monitor']['Id'])); ?>
        <?php echo $this->LiveStream->makeLiveStream($mon['Monitor']['Name'], $streamSrc[$monitor], $mon['Monitor']['Id']); ?>
        <?php echo $this->Html->link($mon['Monitor']['Function'], array('action' => 'edit', $mon['Monitor']['Id'])); ?>
      </div>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
	<?php echo $this->Html->link(
		'Add Monitor',
		array('controller' => 'monitors', 'action' => 'add')
	); ?>

</table>

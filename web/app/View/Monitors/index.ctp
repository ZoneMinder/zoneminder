<?php
  $this->start('sidebar');
  echo $this->Html->link( 'Add Monitor', array('controller' => 'monitors', 'action' => 'add'));
  $this->end();
?>
<div class="row">
  <?php foreach ($monitors as $monitor => $mon): ?>
    <div class="col-sm-6 col-md-3">
      <div class="thumbnail">
        <?php echo $this->LiveStream->makeLiveStream($mon['Monitor']['Name'], $streamSrc[$monitor], $mon['Monitor']['Id'], $width); ?>
        <div class="caption">
          <h3><?php echo $this->Html->link($mon['Monitor']['Name'],array('controller' => 'monitors', 'action' => 'view', $mon['Monitor']['Id'])); ?></h3>
          <p><?php echo $this->Html->link($mon['Monitor']['Function'], array('action' => 'edit', $mon['Monitor']['Id'])); ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php unset($monitor); ?>
</div>

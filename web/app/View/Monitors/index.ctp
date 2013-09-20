<?php
  $this->start('sidebar');
  echo $this->Html->link( 'Add Monitor', array('controller' => 'monitors', 'action' => 'add'));
  $this->end();
?>

<div class="row" id="monitors">
  <?php foreach ($monitors as $monitor => $mon): ?>
    <div class="col-sm-6 col-md-3" id="Monitor_<?= $mon['Monitor']['Id']; ?>">
      <div class="thumbnail">
        <?php 
          if($daemonStatus && $mon['Monitor']['Function'] != "None" && $mon['Monitor']['Enabled'])
            echo $this->LiveStream->makeLiveStream($mon['Monitor']['Name'], $streamSrc[$monitor], $mon['Monitor']['Id'], $mon['Monitor']['Width']); 
          else
            echo $this->LiveStream->showNoImage($mon['Monitor']['Name'], $streamSrc[$monitor], $mon['Monitor']['Id'], $width);
        ?>
        <div class="caption">
          <h4><?php echo $this->Html->link($mon['Monitor']['Name'],array('controller' => 'monitors', 'action' => 'view', $mon['Monitor']['Id'])); ?></h4>
          <p><?php echo $this->Html->link($mon['Monitor']['Function'], array('action' => 'edit', $mon['Monitor']['Id'])); ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php unset($monitor); ?>
</div>

<?php
  $this->Js->get('#monitors');
  $this->Js->sortable(array('complete' => '$.post("/monitors/reorder", $("#monitors").sortable("serialize"))',));
?>
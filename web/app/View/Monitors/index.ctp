<?php
  $this->start('sidebar');
  echo $this->Html->link( 'Add Monitor', array('controller' => 'monitors', 'action' => 'add'));
  $this->end();
?>

<div class="row" id="monitors">
  <?php foreach ($monitors as $monitor => $mon): ?>
    <div class="col-md-4" id="Monitor_<?= $mon['Monitor']['Id']; ?>">
      <div class="thumbnail">
        <?php 
debug($mon);
	echo $this->Html->image($mon['img']['src'], array(
		'alt' => $mon['img']['alt'],
		'id' => $mon['img']['id']
	));
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

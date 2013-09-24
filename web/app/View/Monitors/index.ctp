<?php
  $this->start('sidebar');
  echo $this->Html->link( 'Add Monitor', array('controller' => 'monitors', 'action' => 'add'));
  $this->end();
?>

<div id="monitors" class="js-masonry" data-masonry-options='{ "gutter": 10, "itemSelector": ".monitor" }'>
  <?php foreach ($monitors as $monitor => $mon): ?>
    <div class="monitor" id="Monitor_<?= $mon['Monitor']['Id']; ?>" style="width:<?php $mon['img']['width'];?>">
      <div class="thumbnail">
        <?php echo $this->Html->image($mon['img']['src'], array(
		'alt' => $mon['img']['alt'],
		'id' => $mon['img']['id'],
		'width' => Configure::read('ZM_WEB_LIST_THUMB_WIDTH')
	)); ?>
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

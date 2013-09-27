<?php
  $this->start('sidebar');
  echo $this->Html->link( 'Add Monitor', array('controller' => 'monitors', 'action' => 'add'));
  $this->end();
?>

<div id="monitors">
  <?php foreach ($monitors as $monitor => $mon): ?>
    <div class="panel panel-default monitor pull-left" id="Monitor_<?= $mon['Monitor']['Id']; ?>" style="width:<?php echo Configure::read('ZM_WEB_LIST_THUMB_WIDTH'); ?>px;">
      <div class="panel-heading">
        <h4><?php echo $this->Html->link($mon['Monitor']['Name'],array('controller' => 'monitors', 'action' => 'view', $mon['Monitor']['Id'])); ?></h4>
      </div>
      <div class="thumbnail panel-body">
        <?php echo $this->Html->image($mon['img']['src'], array(
		'alt' => $mon['img']['alt'],
		'id' => $mon['img']['id'],
		'width' => Configure::read('ZM_WEB_LIST_THUMB_WIDTH')
	)); ?>
        <div class="caption">
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

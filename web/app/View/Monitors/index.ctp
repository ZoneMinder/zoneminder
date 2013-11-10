<?php $this->assign('title', 'Monitors'); ?>
<?php $this->start('sidebar'); ?>
<ul class="list-group">
	<li class="list-group-item">
		<a href="/monitors/add/" class="btn btn-success btn-block" role="button">Add Monitor</a>
	</li>
	<li class="list-group-item">
		<a href="/monitors/delete/" class="btn btn-danger btn-block" role="button">Delete Monitor</a>
	</li>
</ul>
<?php $this->end(); ?>

<div id="monitors">
  <?php foreach ($monitors as $monitor => $mon): ?>
    <div class="thumbnail monitor pull-left" id="Monitor_<?= $mon['Monitor']['Id']; ?>">
      <?php echo $this->Html->image($mon['img']['src'], array(
		'alt' => $mon['img']['alt'],
		'id' => $mon['img']['id'],
		'width' => Configure::read('ZM_WEB_LIST_THUMB_WIDTH')
      )); ?>
      <div class="caption">
      <p><?php echo $this->Html->link($mon['Monitor']['Name'],array('controller' => 'monitors', 'action' => 'view', $mon['Monitor']['Id'])); ?></p>
      </div>
    </div>
  <?php endforeach; ?>
  <?php unset($monitor); ?>
</div>

<?php
  $this->Js->get('#monitors');
  $this->Js->sortable(array('complete' => '$.post("/monitors/reorder", $("#monitors").sortable("serialize"))',));
?>

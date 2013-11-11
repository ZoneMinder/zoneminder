<?php
	$this->assign('title', 'Zones');
	echo $this->Form->create();
	foreach ($monitors as $key => $monitor): 
?>

	<div class="panel panel-default pull-left zone" style="width:<?php echo Configure::read('ZM_WEB_LIST_THUMB_WIDTH'); ?>px;">
		<div class="panel-heading">
			<p class="lead"><?php echo $monitor['Monitor']['Name']; ?></p>
		</div>
		<div class="panel-body">
			<div class="thumbnail">
				<?php echo $this->Html->image($monitor['Monitor']['Snapshot'], array('width' => Configure::read('ZM_WEB_LIST_THUMB_WIDTH'), 'alt' => $monitor['Monitor']['Name'])); ?>
				<div class="caption">
					<ol>
					<?php foreach ($monitor['Zone'] as $zone): ?>
						<li>
							<?php echo $this->Form->checkbox($zone['Name'], array('value' => $zone['Id'], 'hiddenField' => false)); ?>
							<?php echo $this->Html->link($zone['Name'], array('controller' => 'zones', 'action' => 'edit', $zone['Id'])); ?>
						</li>
					<?php endforeach; ?>
					</ol>
					<div class="btn-group">
						<button type="button" class="btn btn-success">Add</button>
						<button type="button" class="btn btn-danger">Delete</button>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php
	endforeach;
	echo $this->Form->end();
?>

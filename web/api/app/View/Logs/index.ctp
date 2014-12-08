<div class="logs index">
	<h2><?php echo __('Logs'); ?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('TimeKey'); ?></th>
			<th><?php echo $this->Paginator->sort('Component'); ?></th>
			<th><?php echo $this->Paginator->sort('Pid'); ?></th>
			<th><?php echo $this->Paginator->sort('Level'); ?></th>
			<th><?php echo $this->Paginator->sort('Code'); ?></th>
			<th><?php echo $this->Paginator->sort('Message'); ?></th>
			<th><?php echo $this->Paginator->sort('File'); ?></th>
			<th><?php echo $this->Paginator->sort('Line'); ?></th>
			<th class="actions"><?php echo __('Actions'); ?></th>
	</tr>
	<?php foreach ($logs as $log): ?>
	<tr>
		<td><?php echo h($log['Log']['TimeKey']); ?>&nbsp;</td>
		<td><?php echo h($log['Log']['Component']); ?>&nbsp;</td>
		<td><?php echo h($log['Log']['Pid']); ?>&nbsp;</td>
		<td><?php echo h($log['Log']['Level']); ?>&nbsp;</td>
		<td><?php echo h($log['Log']['Code']); ?>&nbsp;</td>
		<td><?php echo h($log['Log']['Message']); ?>&nbsp;</td>
		<td><?php echo h($log['Log']['File']); ?>&nbsp;</td>
		<td><?php echo h($log['Log']['Line']); ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View'), array('action' => 'view', $log['Log']['TimeKey'])); ?>
			<?php echo $this->Html->link(__('Edit'), array('action' => 'edit', $log['Log']['TimeKey'])); ?>
			<?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $log['Log']['TimeKey']), array(), __('Are you sure you want to delete # %s?', $log['Log']['TimeKey'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
	</table>
	<p>
	<?php
	echo $this->Paginator->counter(array(
	'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
	));
	?>	</p>
	<div class="paging">
	<?php
		echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
		echo $this->Paginator->numbers(array('separator' => ''));
		echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
	?>
	</div>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('New Log'), array('action' => 'add')); ?></li>
	</ul>
</div>

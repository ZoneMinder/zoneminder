<div class="logs view">
<h2><?php echo __('Log'); ?></h2>
	<dl>
		<dt><?php echo __('TimeKey'); ?></dt>
		<dd>
			<?php echo h($log['Log']['TimeKey']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Component'); ?></dt>
		<dd>
			<?php echo h($log['Log']['Component']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Pid'); ?></dt>
		<dd>
			<?php echo h($log['Log']['Pid']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Level'); ?></dt>
		<dd>
			<?php echo h($log['Log']['Level']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Code'); ?></dt>
		<dd>
			<?php echo h($log['Log']['Code']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Message'); ?></dt>
		<dd>
			<?php echo h($log['Log']['Message']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('File'); ?></dt>
		<dd>
			<?php echo h($log['Log']['File']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Line'); ?></dt>
		<dd>
			<?php echo h($log['Log']['Line']); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Log'), array('action' => 'edit', $log['Log']['TimeKey'])); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete Log'), array('action' => 'delete', $log['Log']['TimeKey']), null, __('Are you sure you want to delete # %s?', $log['Log']['TimeKey'])); ?> </li>
		<li><?php echo $this->Html->link(__('List Logs'), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Log'), array('action' => 'add')); ?> </li>
	</ul>
</div>

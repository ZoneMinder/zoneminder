<div class="logs form">
<?php echo $this->Form->create('Log'); ?>
	<fieldset>
		<legend><?php echo __('Add Log'); ?></legend>
	<?php
		echo $this->Form->input('Component');
		echo $this->Form->input('Pid');
		echo $this->Form->input('Level');
		echo $this->Form->input('Code');
		echo $this->Form->input('Message');
		echo $this->Form->input('File');
		echo $this->Form->input('Line');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit')); ?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Logs'), array('action' => 'index')); ?></li>
	</ul>
</div>

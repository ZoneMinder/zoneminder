<?php echo $this->Form->create('Monitor', array( 'inputDefaults' => array( 'legend' => false, 'fieldset' => false))); ?>
<?php echo $this->Form->input('Id', array('type' => 'hidden')); ?>
<div id="tabs">
	<ul>
		<li><a href="#general">General</a></li>
		<li><a href="#source">Source</a></li>
		<li><a href="#timestamp">Timestamp</a></li>
		<li><a href="#buffers">Buffers</a></li>
		<li><a href="#control">Control</a></li>
		<li><a href="#misc">Misc</a></li>
	</ul>


<?php echo $this->element('tabs-general'); ?>
<?php echo $this->element('tabs-source'); ?>
<?php echo $this->element('tabs-timestamp'); ?>
<?php echo $this->element('tabs-buffers'); ?>
<?php echo $this->element('tabs-control'); ?>
<?php echo $this->element('tabs-misc'); ?>


</div>
<?php echo $this->Form->end('Save Monitor'); ?>

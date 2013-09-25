<?php echo $this->element('tabs-nav'); ?>

<?php echo $this->Form->create('Monitor', array( 'inputDefaults' => array( 'legend' => false, 'fieldset' => false))); ?>
<?php echo $this->Form->input('Id', array('type' => 'hidden')); ?>

<div class="tab-content">
<?php echo $this->element('tabs-general'); ?>
<?php echo $this->element('tabs-source'); ?>
<?php echo $this->element('tabs-timestamp'); ?>
<?php echo $this->element('tabs-buffers'); ?>
<?php echo $this->element('tabs-control'); ?>
<?php echo $this->element('tabs-misc'); ?>
</div>

<?php echo $this->Form->end('Save Monitor'); ?>

<?php echo $this->Form->create('Monitor', array(
  'inputDefaults' => array(
    'legend' => false,
    'fieldset' => false,
    'label' => array('class' => array('control-label')),
    'class' => 'form-control'
  )
)); ?>
<?php echo $this->Form->input('Id', array('type' => 'hidden')); ?>

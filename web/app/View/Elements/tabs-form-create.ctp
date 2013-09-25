<?php echo $this->Form->create('Monitor', array(
  'inputDefaults' => array(
    'legend' => false,
    'fieldset' => false,
    'label' => array('class' => array('control-label', 'col-lg-2')),
    'class' => 'form-control'
  ),
  'class' => 'form-horizontal',
)); ?>
<?php echo $this->Form->input('Id', array('type' => 'hidden')); ?>

<div id="general">
<?php
echo $this->Form->input('Name');
echo $this->Form->input('Type', array( 'type' => 'select', 'options' => $typeoptions));
echo $this->Form->input('Function', array('type' => 'select', 'options' => $functionoptions));
echo $this->Form->input('Enabled', array('type' => 'checkbox'));
echo $this->Form->input('MaxFPS');
echo $this->Form->input('AlarmMaxFPS');
echo $this->Form->input('RefBlendPerc');
?>
</div>

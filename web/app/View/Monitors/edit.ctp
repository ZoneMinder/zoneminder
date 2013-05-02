<h2>Edit Monitor</h2>
<?php
    echo $this->Form->create('Monitor');
    echo $this->Form->input('Name');
    $functionoptions = array('Modect' => 'Modect', 'Monitor' => 'Monitor', 'Record' => 'Record', 'None' => 'None', 'Nodect' => 'Nodect', 'Mocord' => 'Mocord');
    echo $this->Form->input('Function', array('type' => 'select', 'options' => $functionoptions));
    echo $this->Form->input('Enabled', array('type' => 'checkbox'));
    echo $this->Form->input('Id', array('type' => 'hidden'));
    echo $this->Form->end('Save Monitor');
?>

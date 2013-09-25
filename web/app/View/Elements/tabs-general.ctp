<?php
      $typeoptions = array(
        'Local' => 'Local',
        'Remote' => 'Remote',
        'File' => 'File',
        'Ffmpeg' => 'Ffmpeg'
      );
      $this->set('typeoptions', $typeoptions);
    
      $functionoptions = array(
        'Modect' => 'Modect',
        'Monitor' => 'Monitor',
        'Record' => 'Record',
        'None' => 'None',
        'Nodect' => 'Nodect',
        'Mocord' => 'Mocord'
      );
      $this->set('functionoptions', $functionoptions);
?>

<div id="general" class="tab-pane active">
<?php
echo $this->Form->input('Name');
echo $this->Form->input('Type', array( 'type' => 'select', 'options' => $typeoptions));
echo $this->Form->input('Function', array('type' => 'select', 'options' => $functionoptions));
echo $this->Form->input('Enabled', array('type' => 'checkbox', 'div' => false, 'class' => false));
echo $this->Form->input('MaxFPS');
echo $this->Form->input('AlarmMaxFPS');
echo $this->Form->input('RefBlendPerc');
?>
</div>

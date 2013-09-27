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
echo $this->Form->input('MaxFPS', array(
  'after' => '<span class="help-block">Limit the maximum capture rate to the specified value.  Do not use with IP / Network cameras, instead limit on the camera itself.</span>'
));
echo $this->Form->input('AlarmMaxFPS', array(
  'after' => '<span class="help-block">Override the above Max FPS option during alarms.</span>'
));
echo $this->Form->input('RefBlendPerc', array(
  'after' => '<span class="help-block">Each analysed image in ZoneMinder is a composite of previous images and is formed by applying the current image as a certain percentage of the previous reference image.  This value specifies that percentage.</span>'
));
?>
</div>

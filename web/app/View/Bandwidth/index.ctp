<h2>Bandwidth</h2>
<?php
  echo $this->Form->create('Bandwidth', array(
    'url' => '/bandwidth',
    'novalidate' => true
  ));
  $options = array('low' => 'Low', 'medium' => 'Medium', 'high' => 'High');
  echo $this->Form->input('Bandwidth', array('type' => 'select', 'options' => $options));
  echo $this->Form->end('Update Bandwidth'); 

?>
</table>

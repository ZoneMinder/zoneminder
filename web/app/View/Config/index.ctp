<h2>Configs</h2>
<?php
  echo $this->Form->create('Config', array(
    'url' => '/config',
    'novalidate' => true
  ));
   foreach ($configs as $config):
	$id = $config['Config']['Id'];
	$inputname = 'Config.' . $id . '.' . $config['Config']['Name'];
	 echo $this->Form->input($inputname, array(
		'default' => $config['Config']['Value'],
		'label' => $config['Config']['Name'],
         	'after' => $config['Config']['Prompt'],
	));
    endforeach;
    unset($config);
  echo $this->Form->end('Save Config'); 

?>
</table>

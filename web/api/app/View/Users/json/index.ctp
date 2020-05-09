<?php
	$array['users'] = $users;
	$array['pagination'] = $this->Paginator->params();
	echo json_encode($array);
?>

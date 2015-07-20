<?php
	$array['events'] = $events;
	$array['pagination'] = $this->Paginator->params();
	echo json_encode($array);
?>

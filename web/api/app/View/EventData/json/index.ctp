<?php
	$array['event_data'] = $event_data;
	$array['pagination'] = $this->Paginator->params();
	echo json_encode($array);
?>

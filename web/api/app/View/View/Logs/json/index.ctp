<?php
	$array['logs'] = $logs;
	$array['pagination'] = $this->Paginator->params();
	echo json_encode($array);
?>

<?php
	$array['events'] = $events;
	$array['pagination'] = $this->Paginator->params();
	if (!isset($array['pagination']['count'])) {
		$array['pagination']['nextPage'] = false;
	}
	echo json_encode($array);
?>

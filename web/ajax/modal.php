<?php
if (empty($_REQUEST['modal'])) {
  ajaxError('Modal Name Not Provided');
  return;
}

$modal = detaintPath($_REQUEST['modal']);
$data = array();

ZM\Debug("Including modals/$modal.php");
# Shouldn't be necessary but at the moment we have last .conf file contents
ob_start();
@$result = include('modals/'.$modal.'.php');
$data['html'] = ob_get_contents();
ob_end_clean();
if (!$result) {
  ajaxError("Unknown modal '".$modal."'");
  return;
}

ajaxResponse($data);
return;
?>

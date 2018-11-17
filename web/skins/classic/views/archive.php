<?php

if (!in_array($_REQUEST['type'], array("zip", "tar"))) die('Unknown file type');
    
if ($_REQUEST['type'] == 'zip') $file = '/var/lib/zoneminder/temp/zmExport.zip';
if ($_REQUEST['type'] == 'tar') $file = '/var/lib/zoneminder/temp/zmExport.tar.gz';

if (!file_exists($file)) die('File does not exists');
    

if (ob_get_level()) ob_end_clean();
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($file).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;



?>

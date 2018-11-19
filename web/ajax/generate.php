<?php


# Check parameters

if ( 
     empty($_REQUEST['eids']) ||
     empty($_REQUEST['generateEncoder']) || 
     empty($_REQUEST['generateFramerate']) ||
     empty($_REQUEST['generateSize']) 
  ) {
  die('Request parameters missing');
}

require_once( '../includes/config.php' );

//die('php generate_job.php "' . http_build_query($_REQUEST) . '" > /dev/null 2>&1 &');

echo exec( 'php ../includes/generate_job.php "' . http_build_query($_REQUEST) . 
  '" >> ' . ZM_PATH_LOGS . '/generate_job.log 2>&1 &');

echo "Background job started (No progress yet)" . PHP_EOL;

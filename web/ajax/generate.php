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

//die('php generate_job.php "' . http_build_query($_REQUEST) . '" > /dev/null 2>&1 &');
echo exec( 'php generate_job.php "' . http_build_query($_REQUEST) . '" >> /var/log/zoneminder/generate_video.log 2>&1 &');

echo "Done" . PHP_EOL;

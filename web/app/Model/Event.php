<?php
class Event extends AppModel {
  public $useTable = 'Events';
  public $primaryKey = 'Id';
  public $belongsTo = array(
	'Monitor' => array(
		'className' => 'Monitor',
		'foreignKey' => 'MonitorId'
	)
  );
  public $hasMany = array(
    'Frame' => array(
      'className' => 'Frame',
      'foreignKey' => 'FrameId',
      'dependent' => true
    )
  );

  function createVideo( $event, $format, $rate, $scale, $overwrite=false ) {
  	
  	  if ($event['Videoed']){
  	  	$videoSrc = "/events/" . $this->getEventPath($event) . "/event.mp4";
  	  	return $videoSrc;
  	  }else{
		  $command = Configure::read('ZM_PATH_BIN')."/zmvideo.pl -e ".$event['Id']." -f ".$format." -r ".sprintf( "%.2F", ($rate/100) );
		  if ( preg_match( '/\d+x\d+/', $scale ) )
			  $command .= " -S ".$scale;
		  else
			  if ( version_compare( phpversion(), "4.3.10", ">=") )
				  $command .= " -s ".sprintf( "%.2F", ($scale/100) );
			  else
				  $command .= " -s ".sprintf( "%.2f", ($scale/100) );
		  if ( $overwrite )
			  $command .= " -o";

		  $result = exec( escapeshellcmd( $command ), $output, $status );
		  $videoSrc = str_replace(Configure::read('ZM_PATH_WEB'), '', $result);
		  return( $status?"":rtrim($videoSrc) );
      }
  }

}
?>

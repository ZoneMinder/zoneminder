<?php
$config = [
  'interface'=>'',
  'bind_interfaces'=>'',
];
if (defined('ZM_PATH_DNSMASQ_CONF'))
  $config += process_configfile(ZM_PATH_DNSMASQ_CONF);
ZM\Debug(print_r($config, true));
foreach ($config as $name=>$value) {
  echo '<li><label>'.$name.'<input type="text" name="'.validHtmlStr($name).'" value="'.validHtmlStr($value).'"/></li>'.PHP_EOL;
}
?>
</div><!--Config-->
<div class="leases">
<?php 
function read_leasefile($file) {
  $leases = [];
  foreach (explode("\n", $contents) as $line) {
    $row = explode(' ', $line);
    $lease = [];
    $lease['expiry'] = $row[0];
    $lease['mac'] = $row[1];
    $lease['ip'] = $row[2];
    $lease['name'] = $row[3];
    $lease['id'] = $row[4];
    $leases[] = $lease;
  }
  return $leases;
}

$count_active=array();
foreach($pools as $pool_id=>$p) {
  $count_active[$pool_id]=0;
}

foreach($data as $ip=>$d) {
  if($d['binding']=="state active") {
    foreach($pools as $pool_id=>$p) {
      if(preg_match("/$p/", $ip)) {
        $count_active[$pool_id]++;
      }
    }
  }
}

?>
</div>

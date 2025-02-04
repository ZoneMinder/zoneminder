<?php
if (!canView('System')) {
  $view = 'error';
  return;
}
?>
<div class="dnsmasq">
  <h2>DHCP Server Config</h2>
  <form name="contentForm" action="?view=options" method="post">
    <input type="hidden" name="object" value="dnsmasq"/>
    <input type="hidden" name="tab" value="dnsmasq"/>
<?php
if (canEdit('System')) {
?>
    <div id="contentButtons">
      <button type="submit" name="action" value="save"><?php echo translate('Save') ?></button>
    </div>
<?php
}
?>
<div class="service"><h2><?php echo translate('Service') ?></h2>
<?php 
if (!file_exists('/usr/sbin/dnsmasq')) {
  echo 'Dnsmasq DHCP server is not installed.<br/>';
} else {
  $active = systemd_isactive('dnsmasq');
  echo 'Dnsmasq service is '.($active ? 'active' : 'inactive').'.';
  if (canEdit('System')) {
    if ($active) {
    echo '<button type="submit" name="action" value="stop">'.translate('Stop').'</button>';
    } else {
    echo '<button type="submit" name="action" value="start">'.translate('Start').'</button>';
    }
  }
}
?>
</div>
    <div class="config"><h2><?php echo translate('Configuration') ?></h2>
      <div class="container">
<?php
$dnsmasq_config = [
  'interface'=>'',
  'bind-interfaces'=>'no',
  'dhcp-range'=>'192.168.1.50,192.168.1.150,12h',
  #'dhcp-rapid-commit'=>'',
  'dhcp-authoritative'=>'no'
];
if (defined('ZM_PATH_DNSMASQ_CONF') and file_exists(ZM_PATH_DNSMASQ_CONF)) {
  $dnsmasq_config = array_merge($dnsmasq_config, process_dnsmasq_configfile(ZM_PATH_DNSMASQ_CONF));
} else {
  ZM\Debug("Either not defined or does not exist ".ZM_PATH_DNSMASQ_CONF);
}

foreach ($dnsmasq_config as $name=>$value) {
  if ($name == 'interface') {
    $interfaces = get_networks();
    $default_interface = $interfaces['default'];
    unset($interfaces['default']);

    echo '<div class="row"><label class="form-label" for="interface">'.translate('Interface').'</label>'.PHP_EOL.
      '<span class="value">'.
      htmlSelect('config[interface]', $interfaces,
        (isset($dnsmasq_config['interface']) ? $dnsmasq_config['interface'] : $default_interface),
        ['data-on-change-this'=>'interface_onchange'],
      ).'</span></div>'.PHP_EOL;
  } else if ($name == 'bind-interfaces') {
    echo '<div class="row"><label class="form-label" for="bind-interfaces">'.translate('Bind Interfaces').'</label>'.PHP_EOL.
      '<span class="value">'.
      html_radio('config[bind-interfaces]', ['yes'=>translate('Yes'), 'no'=>translate('No')], $dnsmasq_config[$name], ['default'=>'yes']).
      '</span></div>'.PHP_EOL;
  } else if ($name == 'dhcp-authoritative') {
    echo '<div class="row"><label class="form-label" for="dhcp-authoritative">'.translate('DHCP Authoritative').'</label>'.PHP_EOL.
      '<span class="value">'.
      html_radio('config[dhcp-authoritative]', ['yes'=>translate('Yes'), 'no'=>translate('No')], $dnsmasq_config[$name], ['default'=>'yes']).
      '</span></div>'.PHP_EOL;
  } else if ($name == 'dhcp-range') {
    $values = explode(',', $value);

    echo '<div class="row"><label class="form-label" for="dhcp-range"> '.translate('DHCP Range').'</label><span class="value">';
    echo '<input type="text" name="config[dhcp-range][min]" value="'.$values[0].'"/>';
    echo ' to <input type="text" name="config[dhcp-range][max]" value="'.$values[1].'"/>';
    echo ' <input type="text" name="config[dhcp-range][expires]" value="'.$values[2].'"/></span></div>'.PHP_EOL;
  } else if ($name == 'dhcp-host') {
    # Handled below
  } else {
    echo '<div class="row"><label class="form-label">'.$name.'</label><span class="value">'.PHP_EOL;
    echo '<input type="text" name="config['.validHtmlStr($name).']" value="'.validHtmlStr($value).'"/></span></div>'.PHP_EOL;
  }
}
?>
      </div>
    </div><!--Config-->
    <div class="leases"><h2>Leases</h2>
<?php 
function process_dnsmasq_configfile($configFile) {
  $configvals = array();
  if (is_readable($configFile)) {
    $cfg = fopen($configFile, 'r') or ZM\Error('Could not open config file: '.$configFile);
    while ( !feof($cfg) ) {
      $str = fgets($cfg, 256);
      if ( preg_match('/^\s*(#.*)?$/', $str) ) {
        continue;
      } else if ( preg_match('/^\s*([^=\s]+)\s*(=\s*[\'"]*(.*?)[\'"]*\s*)?$/', $str, $matches) ) {
        $configvals[$matches[1]] = isset($matches[3]) ? $matches[3] : 'yes';
			} else {
				ZM\Error("Malformed line in config $configFile\n$str");
			}
    }
    fclose($cfg);
  } else {
    ZM\Error('WARNING: dnsmasq configuration file found but is not readable. Check file permissions on '.$configFile);
  }
  return $configvals;
}

function read_leasefile($file) {
  $leases = [];
  $contents = @file_get_contents($file);
  foreach (explode("\n", $contents) as $line) {
    $row = explode(' ', $line);
    if (count($row) != 5) continue;
    $lease = [
      'expiry' => $row[0],
      'mac' => $row[1],
      'ip' => $row[2],
      'name' => $row[3],
      'id' => $row[4]
    ];
    $leases[] = $lease;
  }
  return $leases;
}
?>
<table id="leasesTable" class="table bootstraptable"
  data-check-on-init="true"
  data-mobile-responsive="true"
  data-min-width="562"
>
  <thead>
    <tr>
      <th data-sortable="true" data-field="hostname" class="hostname"><?php echo translate('Hostname') ?></th>
      <th data-sortable="true" data-field="mac" class="mac"><?php echo translate('Mac Address') ?></th>
      <th data-sortable="true" data-field="ip" class="ip"><?php echo translate('IP Address') ?></th>
      <th data-sortable="true" data-field="expires" class="expires"><?php echo translate('Expires') ?></th>
      <th data-sortable="true" data-field="Monitor" class="expires"><?php echo translate('Monitor') ?></th>
    </tr>
  </thead>
  <tbody>
<?php
$monitors_by_ip = array();
foreach (ZM\Monitor::find(['Type'=>'Remote']) as $monitor) {
  if (preg_match('/^(.+)@(.+)$/', $monitor->Host(), $matches)) {
    $monitors_by_ip[gethostbyname($matches[2])] = $monitor;
  } else {
    $monitors_by_ip[gethostbyname($monitor->Host())] = $monitor;
  }
}
foreach (ZM\Monitor::find(['Type'=>'Ffmpeg']) as $monitor) {
  $url_parts = parse_url($monitor->Path());
  if ($url_parts !== false) {
    $monitors_by_ip[gethostbyname($url_parts['host'])] = $monitor;
  } else {
    ZM\Debug('Unable to parse '.$monitor->Path());
  }
}
$leases = read_leasefile('/var/lib/misc/dnsmasq.leases');
foreach ($leases as $lease) {
  echo '
<tr>
  <td class="hostname">'.$lease['name'].'</td>
  <td class="mac">'.$lease['mac'].'</td>
  <td class="ip"><input type="text" pattern="\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}" name="config[dhcp-host]['.$lease['mac'].']" value="'.$lease['ip'].'"/></td>
  <td class="expiry">'.$dateTimeFormatter->format($lease['expiry']).'</td>
  <td class="Monitor">'.(isset($monitors_by_ip[$lease['ip']]) ? $monitors_by_ip[$lease['ip']]->link_to() : '').'</td>
</tr>';
} # end foreach
?>
  </tbody>
</table>
</div>
</form>
<script nonce="<?php echo $cspNonce ?>">
function interface_onchange(e) {
  const range_min = e.form.elements['config[dhcp-range][min]'];
  const range_max = e.form.elements['config[dhcp-range][max]'];
  // Not likely to happen due to defaults set above
  // Complicated by the possible presence of multiple ips on the interface
  if (!range_min.value) {
    // Automatically populate from interface ip
    const value = e.options[e.selectedIndex].value;
    const parts = value.split(' ');
    if (parts.length > 1) {
      const ip = parts[1];
      const ip_parts = ip.split('.');
      range_min.value = ip_parts[0]+'.'+ip_parts[1]+'.'+ip_parts[2]+'.'+'100';
      range_max.value = ip_parts[0]+'.'+ip_parts[1]+'.'+ip_parts[2]+'.'+'200';
    }
  }
}
</script>

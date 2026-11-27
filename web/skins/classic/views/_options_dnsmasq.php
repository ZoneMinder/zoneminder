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
    <div class="col button-block">
      <div id="contentButtons">
        <button type="submit" name="action" value="save"><?php echo translate('Save') ?></button>
      </div>
    </div>
<?php
}
?>
</div>
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
  'dhcp-authoritative'=>'no',
  'dhcp-option'=>['option:router' => '1.2.3.4'],
];
if (defined('ZM_PATH_DNSMASQ_CONF') and file_exists(ZM_PATH_DNSMASQ_CONF)) {
  $dnsmasq_config = array_merge($dnsmasq_config, process_dnsmasq_configfile(ZM_PATH_DNSMASQ_CONF));
} else {
  ZM\Debug('Either not defined or does not exist '.(defined('ZM_PATH_DNSMASQ_CONF') ? ZM_PATH_DNSMASQ_CONF : 'ZM_PATH_DNSMASQ_CONF'));
}

foreach ($dnsmasq_config as $name=>$value) {
  if ($name == 'interface') {
    $interfaces = get_networks();
    $default_interface = isset($interfaces['default']) ? $interfaces['default'] : '';
    unset($interfaces['default']);

    echo '<div class="row"><label class="form-label" for="interface">'.translate('Interface').'</label>'.PHP_EOL.
      '<span class="value">'.
      htmlSelect('config[interface]', $interfaces,
        (!empty($dnsmasq_config['interface']) ? $dnsmasq_config['interface'] : $default_interface),
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
    $values = array_pad(explode(',', $value), 3, '');

    echo '<div class="row"><label class="form-label" for="dhcp-range"> '.translate('DHCP Range').'</label><span class="value">';
    echo '<input type="text" name="config[dhcp-range][min]" value="'.$values[0].'"/>';
    echo ' to <input type="text" name="config[dhcp-range][max]" value="'.$values[1].'"/>';
    echo ' <input type="text" name="config[dhcp-range][expires]" value="'.$values[2].'"/></span></div>'.PHP_EOL;
  } else if ($name == 'dhcp-option') {
    foreach ($dnsmasq_config[$name] as $option_name => $option_value) {
      ZM\Debug($option_name.'=>'.print_r($option_value, true));
      echo '<div class="row"><label class="form-label">'.translate($option_name).'</label><span class="value">'.PHP_EOL;
      echo '<input type="text" name="config['.$name.']['.$option_name.']" value="'.validHtmlStr($option_value).'"/></span></div>'.PHP_EOL;
    } # end foreach option
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
  $our_configvals = array();
  if (is_readable($configFile)) {
    $cfg = fopen($configFile, 'r') or ZM\Error('Could not open config file: '.$configFile);
    while ( !feof($cfg) ) {
      $str = fgets($cfg);
      if ( preg_match('/^\s*(#.*)?$/', $str) ) {
        continue;
      //} else if ( preg_match('/^\s*([^=\s]+)\s*(=\s*option:[^=\s]+)?(=\s*[\'"]*(.*?)[\'"]*\s*)?$/', $str, $matches) ) {
      }
      else if ( preg_match('/^\s*([^=\s]+)\s*=?(.*)$/', $str, $matches) ) {
	      ZM\Debug(print_r($matches, true));
        $name = $matches[1];
        $value = trim($matches[2]);

        if ($name == 'dhcp-option') {
          # these can be arrays
          if (!isset($our_configvals[$name])) {
            $our_configvals[$name] = [];
          }
          $option = explode(',', $value, 2);
          $our_configvals[$name][$option[0]] = isset($option[1]) ? $option[1] : '';
        } else if ($name == 'dhcp-host') {
          # Reservations as this page writes them: dhcp-host=<mac>,<ip>
          if (!isset($our_configvals[$name])) {
            $our_configvals[$name] = [];
          }
          if (preg_match('/^([0-9a-f]{2}(?::[0-9a-f]{2}){5}),(\d{1,3}(?:\.\d{1,3}){3})$/i', $value, $host)) {
            $our_configvals[$name][strtolower($host[1])] = $host[2];
          }
        } else {
          $our_configvals[$name] = $value;
        }
      } else {
	      ZM\Error("Malformed line in config $configFile\n$str");
      }
    }
    fclose($cfg);
  } else {
    ZM\Error('WARNING: dnsmasq configuration file found but is not readable. Check file permissions on '.$configFile);
  }
  return $our_configvals;
}

function dnsmasq_lease_row($lease, $monitors_by_ip, $dateTimeFormatter) {
  # The name comes from the DHCP client, so it and the other lease fields are escaped.
  $name = validHtmlStr($lease['name']);
  $mac = validHtmlStr($lease['mac']);
  $ip = validHtmlStr($lease['ip']);
  return '
<tr>
  <td class="hostname">'.$name.'</td>
  <td class="mac">'.$mac.'</td>
  <td class="ip"><input type="text" pattern="\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}" name="config[dhcp-host]['.$mac.']" value="'.$ip.'"/></td>
  <td class="expiry">'.($lease['expiry'] == 0 ? translate('Never') : $dateTimeFormatter->format((int)$lease['expiry'])).'</td>
  <td class="Monitor">'.(isset($monitors_by_ip[$lease['ip']]) ? $monitors_by_ip[$lease['ip']]->link_to() : '').'</td>
</tr>';
}

# A saved reservation is only posted back if it has a row. A host that is not
# in the leases file at the moment would otherwise lose its reservation on Save.
function dnsmasq_host_rows($leases, $reserved, $monitors_by_ip, $dateTimeFormatter) {
  $html = '';
  foreach ($leases as $lease) {
    $mac = strtolower($lease['mac']);
    if (isset($reserved[$mac])) {
      $lease['ip'] = $reserved[$mac];
      unset($reserved[$mac]);
    }
    $html .= dnsmasq_lease_row($lease, $monitors_by_ip, $dateTimeFormatter);
  }
  foreach ($reserved as $mac => $ip) {
    $html .= dnsmasq_lease_row(['name'=>'', 'mac'=>$mac, 'ip'=>$ip, 'expiry'=>0, 'id'=>''], $monitors_by_ip, $dateTimeFormatter);
  }
  return $html;
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
foreach (ZM\Monitor::find(['Type'=>'Remote', 'Deleted'=>0]) as $monitor) {
  if (preg_match('/^(.+)@(.+)$/', $monitor->Host(), $matches)) {
    $monitors_by_ip[gethostbyname($matches[2])] = $monitor;
  } else {
    $monitors_by_ip[gethostbyname($monitor->Host())] = $monitor;
  }
}
foreach (ZM\Monitor::find(['Type'=>'Ffmpeg', 'Deleted'=>0]) as $monitor) {
  $url_parts = parse_url($monitor->Path());
  if ($url_parts !== false and isset($url_parts['host'])) {
    $monitors_by_ip[gethostbyname($url_parts['host'])] = $monitor;
  } else {
    ZM\Debug('Unable to parse '.ZM\urlForLog($monitor->Path()));
  }
}
$leases = read_leasefile('/var/lib/misc/dnsmasq.leases');
echo dnsmasq_host_rows($leases, isset($dnsmasq_config['dhcp-host']) ? $dnsmasq_config['dhcp-host'] : [], $monitors_by_ip, $dateTimeFormatter);
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

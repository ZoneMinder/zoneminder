<?php
function getOptionsMenuTabs() {
  $tabs = array();
  if (!defined('ZM_FORCE_CSS_DEFAULT') or !defined('ZM_FORCE_SKIN_DEFAULT'))
    $tabs['skins'] = translate('Display');
  $tabs['system'] = translate('System');
  $tabs['auth'] = translate('Authentication');
  $tabs['config'] = translate('Config');
  if (defined('ZM_PATH_DNSMASQ_CONF') and ZM_PATH_DNSMASQ_CONF) {
    $tabs['dnsmasq'] = translate('DHCP');
  }
  $tabs['API'] = translate('API');
  $tabs['servers'] = translate('Servers');
  $tabs['storage'] = translate('Storage');
  $tabs['web'] = translate('Web');
  $tabs['images'] = translate('Images');
  $tabs['logging'] = translate('Logging');
  $tabs['network'] = translate('Network');
  $tabs['mail'] = translate('Email');
  $tabs['upload'] = translate('Upload');
  $tabs['x10'] = translate('X10');
  $tabs['highband'] = translate('HighBW');
  $tabs['medband'] = translate('MediumBW');
  $tabs['lowband'] = translate('LowBW');
  $tabs['users'] = translate('Users');
  $tabs['groups'] = translate('Groups');
  $tabs['control'] = translate('Control');
  $tabs['privacy'] = translate('Privacy');
  $tabs['MQTT'] = translate('MQTT');
  $tabs['telemetry'] = translate('Telemetry');
  $tabs['version'] = translate('Versions');
  
  return $tabs;
}
?>

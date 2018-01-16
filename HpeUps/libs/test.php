<?php
  ini_set('memory_limit', '256M');

  require('snmp.php');

  // test the oid_format function

  $ip = '10.4.150.200'; 		// ip address or hostname
  $community = 'public';		// community string
  $oid = '.1.3.6.1.4.1.232.165.3.2.1';

  $snmp = new snmp();

  $snmp->version = SNMP_VERSION_2;

  // get system uptime
  print_r($snmp->get($ip, $oid, ['community' => $community]));
  print_r($snmp->multi_get($ip, $oid, ['community' => $community]));

  print_r($snmp->bulk_get($ip, $oid, ['community' => $community]));

?>

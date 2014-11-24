<?PHP
/* Copyright 2014, Bergware International.
 * Copyright 2014, Lime Technology
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
require_once 'Helpers.php';

$hot   = $_POST['hot'];
$max   = $_POST['max'];
$disks = parse_ini_file('state/disks.ini',true);
$saved = @parse_ini_file('state/monitor.ini',true);
$shell = '/usr/local/sbin/notify';
extract(parse_plugin_cfg("dynamix",true));

foreach ($disks as $disk) {
  $name = $disk['name'];
  if ($name=='flash' || substr($disk['status'],-3)=='_NP') continue;
  $temp = $disk['temp'];
  $info = "{$disk['id']} ({$disk['device']})";
  $text = my_disk($name).($name=='cache'||$name=='parity'?' disk':'');
// process temperature notifications. Give messages only when changes occur!
  $warn = $temp>=$max?'alert':($temp>=$hot?'warning':'');
  $item = 'temp';
  $last = isset($saved[$item][$name]) ? $saved[$item][$name] : 0;
  if ($warn) {
    if ($temp>$last) {
      exec("$shell -e \"unRAID $text temperature\" -s \"".ucfirst($warn).": $text ".($warn=='alert'?'overheated (':'is hot (').my_temp($temp).")\" -d \"$info\" -i \"$warn\" -x");
      $saved[$item][$name] = $temp;
    }
  } else {
    if ($last>0) {
      exec("$shell -e \"unRAID $text message\" -s \"Notice: $text returned to normal temperature\" -d \"$info\" -i \"normal\" -x");
      unset($saved[$item][$name]);
    }
  }
// process disk operation notifications. Give messages only when changes occur!
  $warn = strtok($disk['color'],'-');
  $item = 'disk';
  $last = isset($saved[$item][$name]) ? $saved[$item][$name] : "";
  switch ($warn) {
  case 'red':
    if ($warn!=$last) {
      exec("$shell -e \"unRAID $text error\" -s \"Alert: $text in error state\" -d \"$info\" -i \"alert\"");
      $saved[$item][$name] = $warn;
    }
  break;
  case 'yellow':
    if ($warn!=$last) {
      exec("$shell -e \"unRAID $text error\" -s \"Warning: $text has invalid data\" -d \"$info\" -i \"warning\"");
      $saved[$item][$name] = $warn;
    }
  break;
  default:
    if ($last) {
      exec("$shell -e \"unRAID $text message\" -s \"Notice: $text returned to normal operation\" -d \"$info\" -i \"normal\"");
      unset($saved[$item][$name]);
    }
  break;}
// process disk SMART notifications. Give messages only when changes occur!
  $warn = strlen(exec("smartctl -n standby -q errorsonly -H /dev/{$disk['device']}"));
  $item = 'smart';
  $last = isset($saved[$item][$name]) ? $saved[$item][$name] : 0;
  if ($warn) {
    if (!$last) {
      exec("$shell -e \"unRAID $text SMART failure\" -s \"Alert: $text failed SMART health check\" -d \"$info\" -i \"alert\"");
      $saved[$item][$name] = $warn;
    }
  } else {
    if ($last) {
      exec("$shell -e \"unRAID $text SMART message\" -s \"Notice: $text passed SMART health check\" -d \"$info\" -i \"normal\"");
      unset($saved[$item][$name]);
    }
  }
}
if ($saved) {
  $text = "";
  foreach ($saved as $item => $block) {
    $text .= "[$item]\n";
    foreach ($block as $key => $value) $text .= "$key=\"$value\"\n";
  }
  file_put_contents('state/monitor.ini', $text);
}

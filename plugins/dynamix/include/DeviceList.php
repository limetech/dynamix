<?PHP
/* Copyright 2014, Lime Technology
 * Copyright 2014, Bergware International.
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

$path    = $_POST['path'];
$width   = $_POST['width'];
$var     = parse_ini_file("state/var.ini");
$devs    = parse_ini_file("state/devs.ini",true);
$disks   = parse_ini_file("state/disks.ini",true);
$screen  = '/tmp/screen_buffer';

$temps=0; $counts=0; $fsSize=0; $fsUsed=0; $fsFree=0; $reads=0; $writes=0; $errors=0; $row=0;

extract(parse_plugin_cfg("dynamix",true));

function device_info($disk) {
  global $path, $width, $var, $display, $screen;
  $href = $disk['name'];
  if ($href != 'preclear') {
    $name = my_disk($href);
    $type = $name;
    if (strpos($href,'disk') === 0) {
      $type = "Data";
    } else if (strpos($href,'cache') === 0) {
      $type = "Cache";
    }
  } else {
    $name = $disk['device'];
    $type = 'Preclear';
    $href = "{$disk['device']}&file=$screen";
  }
  $action = strpos($disk['color'],'blink')===false ? "down" : "up";
  $a = "<a href='#' class='info nohand' onclick='return false'>";
  $spin_disk = "";
  $title = "";
  if ($display['spin'] && $var['fsState']=="Started") {
    $a = "<a href='update.htm?cmdSpin{$action}={$href}' class='info' target='progressFrame'>";
    $title = "Spin $action";
    $spin_disk = "<img src='/webGui/images/$action.png' class='iconwide'>Spin $action disk<br>";
  }
  $ball = "/webGui/images/{$disk['color']}.png";
  $left = ($width>1590 && $display['spin']) ? " class='left'" : "";

  if ($type=='Parity' || $type=='Data') {
    $status = "{$a}
    <img src='$ball' title='$title' class='icon' onclick=\"$.removeCookie('one',{path:'/'});\"><span{$left}>
    <img src='/webGui/images/green-on.png' class='icon'>Normal operation<br>
    <img src='/webGui/images/yellow-on.png' class='icon'>Invalid data content<br>
    <img src='/webGui/images/red-on.png' class='icon'>Device disabled<br>
    <img src='/webGui/images/blue-on.png' class='icon'>New device not in array<br>
    <img src='/webGui/images/green-blink.png' class='icon'>Device spun-down<br>
    <img src='/webGui/images/grey-off.png' class='icon'>No device present<br>
    {$spin_disk}</span></a>";
  } else if ($type=='Cache') {
    $status = "{$a}
    <img src='$ball' title='$title' class='icon' onclick=\"$.removeCookie('one',{path:'/'});\"><span{$left}>
    <img src='/webGui/images/green-on.png' class='icon'>Normal operation<br>
    <img src='/webGui/images/blue-on.png' class='icon'>New device, not in pool<br>
    <img src='/webGui/images/green-blink.png' class='icon'>Device spun-down<br>
    <img src='/webGui/images/grey-off.png' class='icon'>No device present<br>
    {$spin_disk}</span></a>";
  } else {
    $status = "<img src='$ball' class='icon'>";
  }
  $link = strpos($disk['status'], '_NP')===false ? "<a href='$path/$type?name=$href'>$name</a>" : $name;
  return $status.$link;
}
function device_browse($disk) {
  global $path;
  if ($disk['fsStatus']=='Mounted'):
    $dir = $disk['name']=="flash" ? "/boot" : "/mnt/{$disk['name']}";
    return "<a href='$path/Browse?dir=$dir'><img src='/webGui/images/explore.png' title='Browse $dir'></a>";
  endif;
}
function device_desc($disk) {
  global $var;
  $out = "{$disk['id']} ({$disk['device']})";
  if ($var['fsState']=='Stopped') {
    $out .= " - " . my_scale($disk['size']*1024, $unit) . " " . $unit;
  }
  return $out;
}
function assignment($disk) {
  global $devs, $screen;
  $out = "<form method='POST' name=\"{$disk['name']}Form\" action='/update.htm' target='progressFrame'><input type='hidden' name='changeDevice' value='Apply'>";
  $out .= "<select name=\"slotId.{$disk['idx']}\" onChange=\"{$disk['name']}Form.submit()\">";
  $empty = ($disk['idSb']!="" ? "no device" : "unassigned");
  if ($disk['id']!=""):
    $out .= "<option value=\"{$disk['id']}\" selected>".device_desc($disk)."</option>";
    $out .= "<option value=''>$empty</option>";
  else:
    $out .= "<option value='' selected>$empty</option>";
  endif;
  foreach ($devs as $dev):
    if (!file_exists("{$screen}_{$dev['device']}")) $out .= "<option value=\"{$dev['id']}\">".device_desc($dev)."</option>";
  endforeach;
  $out .= "</select></form>";
  return $out;
}
function render_used_and_free($disk) {
  global $display;
  if ($disk['name']=='parity' || $disk['fsStatus']=='-') {
    echo "<td>-</td><td>-</td>";
  } else if ($disk['fsStatus']=='Mounted') {
    if (!$display['text']) {
      echo "<td>".my_scale($disk['fsUsed']*1024, $unit)." $unit</td>";
      echo "<td>".my_scale($disk['fsFree']*1024, $unit)." $unit</td>";
    } else {
      $free = round(100*$disk['fsFree']/$disk['sizeSb']);
      $used = 100-$free;
      echo "<td><div class='usage-disk'><span style='margin:0;width:{$used}%' class='".usage_color($used,false)."'><span>".my_scale($disk['fsUsed']*1024, $unit)." $unit</span></span></div></td>";
      echo "<td><div class='usage-disk'><span style='margin:0;width:{$free}%' class='".usage_color($free,true)."'><span>".my_scale($disk['fsFree']*1024, $unit)." $unit</span></span></div></td>";
    }
  } else {
    if (!$display['text']) {
      echo "<td></td><td>{$disk['fsStatus']}</td>";
    } else {
      echo "<td><div class='usage-disk'><span style='margin:0;width:0'></span></div></td>";
      echo "<td><div class='usage-disk'><span style='margin:0;width:0'><span>{$disk['fsStatus']}</span></span></div></td>";
    }
  }
}
function array_offline($disk) {
  global $row;
  echo "<tr class='tr_row".($row^=1)."'>";
  switch ($disk['status']) {
  case "DISK_NP":
    echo "<td>".device_info($disk)."</td>";
    echo "<td colspan='10'>".assignment($disk)."</td>";
  break;
  case "DISK_OK":
    echo "<td>".device_info($disk)."</td>";
    echo "<td>".assignment($disk)."</td>";
    echo "<td>".my_temp($disk['temp'])."</td>";
    echo "<td>".my_scale($disk['sizeSb']*1024, $unit)." $unit</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  case "DISK_NP_OK":
    echo "<td>".device_info($disk)."</td>";
    echo "<td>".assignment($disk)."</td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  case "DISK_INVALID":
    echo "<td>".device_info($disk)."</td>";
    echo "<td>".assignment($disk)."</td>";
    echo "<td>".my_temp($disk['temp'])."</td>";
    echo "<td>".my_scale($disk['sizeSb']*1024, $unit)." $unit</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  case "DISK_DSBL":
    echo "<td>".device_info($disk)."</td>";
    echo "<td>".assignment($disk)."</td>";
    echo "<td>".my_temp($disk['temp'])."</td>";
    echo "<td>".my_scale($disk['sizeSb']*1024, $unit)." $unit</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  case "DISK_DSBL_NP":
  if ($disk['name']=="parity") {
    echo "<td>".device_info($disk)."</td>";
    echo "<td colspan='9'>".assignment($disk)."</td>";
  } else {
    echo "<td>".device_info($disk)."<span class='diskinfo'><em>Not installed</em></span></td>";
    echo "<td>".assignment($disk)."<em>{$disk['idSb']}</em></td>";
    echo "<td>-</td>";
    echo "<td><em>".my_scale($disk['sizeSb']*1024, $unit)." $unit</em></td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  }
  break;
  case "DISK_DSBL_NEW":
    echo "<td>".device_info($disk)."</td>";
    echo "<td>".assignment($disk)."</td>";
    echo "<td>".my_temp($disk['temp'])."</td>";
    echo "<td>".my_scale($disk['sizeSb']*1024, $unit)." $unit</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  case "DISK_NP_MISSING":
    echo "<td>".device_info($disk)."<span class='diskinfo'><em>Missing</em></span></td>";
    echo "<td>".assignment($disk)."<em>{$disk['idSb']}</em></td>";
    echo "<td>-</td>";
    echo "<td><em>".my_scale($disk['sizeSb']*1024, $unit)." $unit</em></td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  case "DISK_WRONG":
    echo "<td>".device_info($disk)."<span class='diskinfo'><em>Wrong</em></span></td>";
    echo "<td>".assignment($disk)."<em>{$disk['idSb']}</em></td>";
    echo "<td>".my_temp($disk['temp'])."</td>";
    echo "<td>".my_scale($disk['size']*1024, $unit)." $unit<br><em>".my_scale($disk['sizeSb']*1024, $unit)." $unit</em></td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  case "DISK_NEW":
    echo "<td>".device_info($disk)."</td>";
    echo "<td>".assignment($disk)."</td>";
    echo "<td>".my_temp($disk['temp'])."</td>";
    echo "<td>".my_scale($disk['sizeSb']*1024, $unit)." $unit</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td></td>";
  break;
  }
  echo "</tr>";
}
function array_online($disk) {
  global $display, $temps, $counts, $fsSize, $fsUsed, $fsFree, $reads, $writes, $errors, $row;
  if (is_numeric($disk['temp'])) {
    $temps += $disk['temp'];
    $counts++;
  }
  $reads += $disk['numReads'];
  $writes += $disk['numWrites'];
  $errors += $disk['numErrors'];
  if (isset($disk['fsFree']) && $disk['name']!='parity') {
    $disk['fsUsed'] = $disk['fsSize'] - $disk['fsFree'];
    $fsSize += $disk['fsSize'];
    $fsFree += $disk['fsFree'];
    $fsUsed += $disk['fsUsed'];
  }
  echo "<tr class='tr_row".($row^=1)."'>";
  switch ($disk['status']) {
  case "DISK_NP":
// Suppress empty slots to keep device list short
//    echo "<td>".device_info($disk)."</td>";
//    echo "<td colspan='10'>Not installed</td>";
  break;
  case "DISK_NP_OK":
    echo "<td>".device_info($disk)."</td>";
    echo "<td>Not Installed</td>";
    echo "<td></td>";
    echo "<td></td>";
    render_used_and_free($disk);
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td>{$disk['fsType']}</td>";
    echo "<td>".device_browse($disk)."</td>";
  break;
  case "DISK_DSBL_NP":
    echo "<td>".device_info($disk)."</td>";
  if ($disk['name']=="parity") {
    echo "<td colspan='10'>Not installed</td>";
  } else {
    echo "<td><em>Not installed</em></td>";
    echo "<td>-</td>";
    echo "<td><em>".my_scale($disk['sizeSb']*1024, $unit)." $unit</em></td>";
    render_used_and_free($disk);
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>-</td>";
    echo "<td>".($disk['name']=="parity" ? "-" : $disk['fsType'])."</td>";
    echo "<td>".device_browse($disk)."</td>";
  }
  break;
  default:
    echo "<td>".device_info($disk)."</td>";
    echo "<td>".device_desc($disk)."</td>";
    echo "<td>".my_temp($disk['temp'])."</td>";
    echo "<td>".my_scale($disk['sizeSb']*1024, $unit)." $unit</td>";
    render_used_and_free($disk);
    echo "<td>".my_number($disk['numReads'])."</td>";
    echo "<td>".my_number($disk['numWrites'])."</td>";
    echo "<td>".my_number($disk['numErrors'])."</td>";
    echo "<td>".($disk['name']=="parity" ? "-" : $disk['fsType'])."</td>";
    echo "<td>".device_browse($disk)."</td>";
  break;
  }
  echo "</tr>";
}
function my_clock($time) {
  if (!$time) return 'less than a minute';
  $days = floor($time/1440);
  $hour = $time/60%24;
  $mins = $time%60;
  return plus($days,'day',($hour|$mins)==0).plus($hour,'hour',$mins==0).plus($mins,'minute',true);
}
function show_totals($text) {
  global $display, $temps, $counts, $fsSize, $fsUsed, $fsFree, $reads, $writes, $errors;
  echo "<tr class='tr_last'>";
  echo "<td><img src='/webGui/images/sum.png' class='icon'>Total</td>";
  echo "<td>$text</td>";
  echo "<td>".($counts>0?my_temp(round($temps/$counts, 1)):'*')."</td>";
  echo "<td>".my_scale($fsSize*1024, $unit)." $unit</td>";
  if (!$display['text']) {
    echo "<td>".my_scale($fsUsed*1024, $unit)." $unit</td>";
    echo "<td>".my_scale($fsFree*1024, $unit)." $unit</td>";
  } else {
    $free = round(100*$fsFree/$fsSize);
    $used = 100-$free;
    echo "<td><div class='usage-disk'><span style='margin:0;width:{$used}%' class='".usage_color($used,false)."'><span>".my_scale($fsUsed*1024, $unit)." $unit</span></span></div></td>";
    echo "<td><div class='usage-disk'><span style='margin:0;width:{$free}%' class='".usage_color($free,true)."'><span>".my_scale($fsFree*1024, $unit)." $unit</span></span></div></td>";
  }
  echo "<td>".my_number($reads)."</td>";
  echo "<td>".my_number($writes)."</td>";
  echo "<td>".my_number($errors)."</td>";
  echo "<td></td>";
  echo "<td></td>";
  echo "</tr>";
}
function array_slots() {
  global $var;
  $min = max($var['sbNumDisks'], 2);
  $max = max($var['MAX_ARRAYSZ'] - max($var['SYS_CACHE_SLOTS']-1, 0), 2);
  $out = "";
  $out .= "<form method='POST' action='/update.htm' target='progressFrame'>";
  $out .= "<input type='hidden' name='changeNames' value='Apply'>";
  $out .= "<select name='SYS_ARRAY_SLOTS' onChange='this.form.submit()'>";
  for ($n=$min; $n<=$max; $n++) {
    $selected = ($n == $var['SYS_ARRAY_SLOTS'])? " selected" : "";
    $out .= "<option value='$n'{$selected}>$n</option>";
  }
  $out .= "</select></form>";
  return $out;
}
function cache_slots() {
  global $var;
  $min = $var['cacheSbNumDisks'];
  $max = $var['MAX_DEVICES'] - max($var['SYS_ARRAY_SLOTS'], 2);
  $out = "";
  $out .= "<form method='POST' action='/update.htm' target='progressFrame'>";
  $out .= "<input type='hidden' name='changeNames' value='Apply'>";
  $out .= "<select name='SYS_CACHE_SLOTS' onChange='this.form.submit()'>";
  for ($n=$min; $n<=$max; $n++) {
    $option = $n ? $n : "none";
    $selected = ($n == $var['SYS_CACHE_SLOTS'])? " selected" : "";
    $out .= "<option value='$n'{$selected}>$option</option>";
  }
  $out .= "</select></form>";
  return $out;
}
switch ($_POST['device']) {
case 'array':
  switch ($var['fsState']) {
  case 'Started':
    foreach ($disks as $disk) {if ($disk['type']=='Parity' || $disk['type']=='Data') array_online($disk);}
    if ($display['total'] && $var['mdNumProtected']>1) show_totals("Array of ".my_word($var['mdNumProtected'])." disks".($disks['parity'][status]=='DISK_OK' ? " (including parity disk)" : ""));
  break;
  case 'Stopped':
    foreach ($disks as $disk) {if ($disk['type']=='Parity' || $disk['type']=='Data') array_offline($disk);}
    echo "<tr class='tr_last'><td><img src='/webGui/images/sum.png' class='icon'>Slots:</td><td colspan='10'>".array_slots()."</td></tr>";
    echo "<tr><td colspan='11'></td></tr>";
  break;};
break;
case 'flash':
  $disk = &$disks['flash'];
  $disk['fsUsed'] = $disk['size'] - $disk['fsFree'];
  echo "<tr class='tr_row1'>";
  echo "<td>".device_info($disk)."</td>";
  echo "<td>".device_desc($disk)."</td>";
  echo "<td>*</td>";
  echo "<td>".my_scale($disk['size']*1024, $unit)." $unit</td>";
  if (!$display['text']) {
    echo "<td>".my_scale($disk['fsUsed']*1024, $unit)." $unit</td>";
    echo "<td>".my_scale($disk['fsFree']*1024, $unit)." $unit</td>";
  } else {
    $free = round(100*$disk['fsFree']/$disk['size']);
    $used = 100-$free;
    echo "<td><div class='usage-disk'><span style='margin:0;width:{$used}%' class='".usage_color($used,false)."'><span>".my_scale($disk['fsUsed']*1024, $unit)." $unit</span></span></div></td>";
    echo "<td><div class='usage-disk'><span style='margin:0;width:{$free}%' class='".usage_color($free,true)."'><span>".my_scale($disk['fsFree']*1024, $unit)." $unit</span></span></div></td>";
  }
  echo "<td>".$disk['numReads']."</td>";
  echo "<td>".$disk['numWrites']."</td>";
  echo "<td>".$disk['numErrors']."</td>";
  echo "<td>{$disk['fsType']}</td>";
  echo "<td>".device_browse($disk)."</td>";
  echo "</tr>";
break;
case 'cache':
  switch ($var['fsState']) {
  case 'Started':
    foreach ($disks as $disk) {if ($disk['type']=='Cache') array_online($disk);}
    if ($display['total'] && $var['cacheSbNumDisks']>1) show_totals("Pool of ".my_word($var['cacheSbNumDisks'])." disks");
  break;
  case 'Stopped':
    foreach ($disks as $disk) {if ($disk['type']=='Cache') array_offline($disk);}
    echo "<tr class='tr_last'><td><img src='/webGui/images/sum.png' class='icon'>Slots:</td><td colspan='10'>".cache_slots()."</td></tr>";
    echo "<tr><td colspan='11'></td></tr>";
  break;}
break;
case 'open':
  $status = isset($confirm['preclear']) ? '' : '_NP';
  foreach ($devs as $dev) {
    $dev['name'] = 'preclear';
    $dev['color'] = exec("hdparm -C /dev/{$dev['device']}|grep 'standby'") ? 'blue-blink' : 'blue-on';
    $dev['temp'] = $dev['color']=='blue-on' ? exec("smartctl -A /dev/{$dev['device']}|awk '/Temperature_Celsius/{print \$10}'") : '*';
    $dev['status'] = $status;
    echo "<tr class='tr_row".($row^=1)."'>";
    echo "<td>".device_info($dev)."</td>";
    echo "<td>".device_desc($dev)."</td>";
    echo "<td>".my_temp($dev['temp'])."</td>";
    echo "<td>".my_scale($dev['size']*1024, $unit)." $unit</td>";
    if (file_exists("/tmp/preclear_stat_{$dev['device']}")) {
      $text = exec("cut -d'|' -f3 /tmp/preclear_stat_{$dev['device']} | sed 's:\^n:\<br\>:g'");
      if (strpos($text,'Total time')===false) $text = 'Preclear in progress... '.$text;
      echo "<td colspan='7' style='text-align:right'><em>$text</em></td>";
    } else
      echo "<td colspan='7'></td>";
    echo "</tr>";
  }
break;
case 'parity':
  $data = array();
  if ($var['mdResync']>0) {
    $data[] = my_scale($var['mdResync']*1024, $unit)." $unit";
    $data[] = my_clock(floor(($var['currTime']-$var['sbUpdated'])/60));
    $data[] = my_scale($var['mdResyncPos']*1024, $unit)." $unit (".number_format(($var['mdResyncPos']/($var['mdResync']/100+1)),1,substr($display['number'],0,1),'')." %)";
    $data[] = my_scale($var['mdResyncDb']/$var['mdResyncDt']*1024, $unit, 1)." $unit/sec";
    $data[] = my_clock(round(((($var['mdResyncDt']*(($var['mdResync']-$var['mdResyncPos'])/($var['mdResyncDb']/100+1)))/100)/60),0));
    $data[] = $var['sbSyncErrs'];
    echo implode(';',$data);
  }
break;}
?>

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
$path = '/webGui/images';

function my_insert(&$source,$string) {
  $source = substr_replace($source,$string,4,0);
}
function my_smart(&$source,$name) {
  global $path;
  $saved = @parse_ini_file("/var/local/emhttp/monitor.ini",true);
  $last = isset($saved["smart"]["$name.5"]) ? $saved["smart"]["$name.5"] : 0;
  $smart = exec("awk '$1==5 {print $10}' /var/local/emhttp/smart/$name");
  $thumb = $smart>$last ? 'bad' : 'good';
  my_insert($source, "<a href=\"/Main/Data?name=$name\" onclick=\"$.cookie('one','tab2',{path:'/'})\" title=\"$smart reallocated sectors\"><img src=\"$path/$thumb.png\"></a>");
}
function my_usage(&$source,$used) {
  my_insert($source, $used ? "<div class='usage-disk all'><span style='width:$used'>$used</span></div>" : "-");
}
function my_temp($value,$unit) {
  return ($unit=='C' ? $value : round(9/5*$value+32))." &deg;$unit";
}
function my_clock($time) {
  if (!$time) return 'less than a minute';
  $days = floor($time/1440);
  $hour = $time/60%24;
  $mins = $time%60;
  return plus($days,'day',($hour|$mins)==0).plus($hour,'hour',$mins==0).plus($mins,'minute',true);
}
function plus($val,$word,$last) {
  return $val>0?(($val||$last)?($val.' '.$word.($val!=1?'s':'').($last ?'':', ')):''):'';
}
switch ($_POST['cmd']) {
case 'disk':
  $i = 2;
  $disks = parse_ini_file("state/disks.ini",true);
  $devs  = parse_ini_file("state/devs.ini",true);
  $row1 = array_fill(0,26,"<td></td>"); my_insert($row1[0],"Active");
  $row2 = array_fill(0,26,"<td></td>"); my_insert($row2[0],"Inactive");
  $row3 = array_fill(0,26,"<td></td>"); my_insert($row3[0],"Unassigned");
  $row4 = array_fill(0,26,"<td></td>"); my_insert($row4[0],"Faulty");
  $row5 = array_fill(0,26,"<td></td>"); my_insert($row5[0],"Heat alarm");
  $row6 = array_fill(0,26,"<td></td>"); my_insert($row6[0],"SMART status");
  $row7 = array_fill(0,26,"<td></td>"); my_insert($row7[0],"Utilization");
  foreach ($disks as $disk) {
    $state = $disk['color'];
    $n = 0;
    switch ($disk['type']) {
    case 'Parity':
      if ($disk['status']!='DISK_NP') $n = 1;
    break;
    case 'Data':
      if ($disk['status']!='DISK_NP') $n = $i++;
    break;
    case 'Cache':
      if ($disk['status']!='DISK_NP') $n = $i++;
      if ($disk['name']!='cache') $disk['fsStatus']=='-';
    break;}
    if ($n>0) {
      switch ($state) {
      case 'grey-off':
      break; //ignore
      case 'green-on':
        my_insert($row1[$n],"<img src=$path/$state.png>");
      break;
      case 'green-blink':
        my_insert($row2[$n],"<img src=$path/$state.png>");
      break;
      case 'blue-on':
      case 'blue-blink':
        my_insert($row3[$n],"<img src=$path/$state.png>");
      break;
      default:
        my_insert($row4[$n],"<img src=$path/$state.png>");
      break;}
      $temp = $disk['temp'];
      if ($temp>=$_POST['hot']) my_insert($row5[$n],"<span class='heat-img'><img src='$path/".($temp>=$_POST['max']?'max':'hot').".png'></span><span class='heat-text' style='display:none'>".my_temp($temp,$_POST['unit'])."</span>");
      if ($disk['device'] && !strpos($state,'blink')) my_smart($row6[$n],$disk['name']);
      my_usage($row7[$n],($n>1 && $disk['fsStatus']=='Mounted')?(round((1-$disk['fsFree']/$disk['sizeSb'])*100).'%'):'');
    }
  }
  foreach ($devs as $dev) my_insert($row3[$i++],"<img src=$path/blue-on.png>");
  echo "<tr>".implode('',$row1)."</tr>";
  echo "<tr>".implode('',$row2)."</tr>";
  echo "<tr>".implode('',$row3)."</tr>";
  echo "<tr>".implode('',$row4)."</tr>";
  echo "<tr>".implode('',$row5)."</tr>";
  echo "<tr>".implode('',$row6)."</tr>";
  echo "<tr>".implode('',$row7)."</tr>";
break;
case 'sys':
  exec("awk '/^Mem(Total|Available)/ {print $2}' /proc/meminfo",$memory);
  $cpu = min(@file_get_contents('state/cpuload.ini'),100);
  $mem = max(round((1-$memory[1]/$memory[0])*100),0);
  echo "{$cpu}%#{$mem}%";
break;
case 'cpu':
  if (file_exists("/proc/xen")) {
    exec("xenpm get-cpufreq-states|awk '/^current frequency/ {print \$4\" \"\$5}'",$speeds);
  } else {
    exec("awk '/^cpu MHz/ {printf\"%4.0f MHz\\n\", $4}' /proc/cpuinfo",$speeds);
  }
  echo implode('#',$speeds);
break;
case 'fan':
  exec("sensors -uA 2>/dev/null|awk '/fan[0-9]_input/{print $2+0\" RPM\"}'",$rpms);
  echo implode('#',$rpms);
break;
case 'port':
  switch ($_POST['view']) {
  case 'main':
    $ports = explode(',',$_POST['ports']); $i = 0;
    foreach ($ports as $port) {
      unset($info);
      if ($port=='bond0') {
        $ports[$i++] = exec("awk '/^Bonding Mode/' /proc/net/bonding/$port|cut -d: -f2");
      } else if ($port=='lo') {
        $ports[$i++] = str_replace('yes','loopback',exec("ethtool $port|awk '/Link detected/{print $3}'"));
      } else {
        exec("ethtool $port|awk '/Speed:|Duplex:/{print $2}'",$info);
        $ports[$i++] = $info[0][0]!='U' ? "{$info[0]} - ".strtolower($info[1])." duplex" : "not connected";
      }
    }
  break;
  case 'port': exec("ifconfig -s|awk '/^(bond|eth|lo)/{print $3\"#\"$7}'",$ports); break;
  case 'link': exec("ifconfig -s|awk '/^(bond|eth|lo)/{print \"Errors: \"$4\"<br>Drops: \"$5\"<br>Overruns: \"$6\"#Errors: \"$8\"<br>Drops: \"$9\"<br>Overruns: \"$10}'",$ports); break;
  default: $ports = array();}
  echo implode('#',$ports);
break;
case 'parity':
  $var  = parse_ini_file("state/var.ini");
  echo "<span class='orange p0'><strong>".($var['mdNumInvalid']==0 ? 'Parity-Check' : ($var['mdInvalidDisk']==0 ? 'Parity-Sync' : 'Data-Rebuild'))." in progress... Completed: ".number_format(($var['mdResyncPos']/($var['mdResync']/100+1)),0)." %.</strong></span>".
    "<br><em>Elapsed time: ".my_clock(floor(($var['currTime']-$var['sbUpdated'])/60)).". Estimated finish: ".my_clock(round(((($var['mdResyncDt']*(($var['mdResync']-$var['mdResyncPos'])/($var['mdResyncDb']/100+1)))/100)/60),0))."</em>";
break;
case 'shares':
   $names = explode(',',$_POST['names']);
   switch ($_POST['com']) {
   case 'smb':
     exec("lsof /mnt/user /mnt/disk* 2>/dev/null | awk '/^smbd/ && $0!~/\.AppleD(B|ouble)/ && $5==\"REG\"'|awk -F/ '{print $4}'",$lsof);
     $counts = array_count_values($lsof); $count = array();
     foreach ($names as $name) $count[] =  isset($counts[$name]) ? $counts[$name] : 0;
     echo implode('#',$count);
	 break;
   case 'afp':
   case 'nfs':
   // not available
   break;}
break;}

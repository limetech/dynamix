<?PHP
/* Copyright 2015, Lime Technology
 * Copyright 2015, Bergware International.
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
require_once('Wrappers.php');

function normalize($text) {
  return "<td>".ucfirst(strtolower(str_replace('_',' ',$text)))."</td>";
}

function duration(&$hrs) {
  $time = ceil(time()/3600)*3600;
  $now = new DateTime("@$time");
  $poh = new DateTime("@".($time-$hrs*3600));
  $age = date_diff($poh,$now);
  $hrs = "$hrs (".($age->y?"{$age->y}y, ":"").($age->m?"{$age->m}m, ":"").($age->d?"{$age->d}d, ":"")."{$age->h}h)";
}

function spindownDelay($port) {
  $disks = parse_ini_file("/var/local/emhttp/disks.ini",true);
  foreach ($disks as $disk) {
    if ($disk['device']==$port) { file_put_contents("/var/tmp/diskSpindownDelay.{$disk['idx']}", $disk['spindownDelay']); break; }
  }
}

$port  = $_POST['port'];
switch ($_POST['cmd']) {
case "attributes":
  $unraid = parse_plugin_cfg("dynamix",true);
  $events = explode('|', $unraid['notify']['events']);
  $temps = array(190,194);
  $max = $unraid['display']['max'];
  $hot = $unraid['display']['hot'];
  exec("smartctl -A /dev/$port|awk 'NR>7'",$output);
  foreach ($output as $line) {
    if (!$line) continue;
    $info = explode(' ', trim(preg_replace('/\s+/',' ',$line)), 10);
    $color = "";
    if (array_search($info[0], $events)!==false && $info[9]>0) $color = " class='orange-text'";
    else if (array_search($info[0], $temps)!==false) {
      if ($info[9]>=$max) $color = " class='red-text'"; else if ($info[9]>=$hot) $color = " class='orange-text'";
    }
    if ($info[0]==9 && is_numeric($info[9])) duration($info[9]);
    echo "<tr{$color}>".implode('',array_map('normalize', $info))."</tr>";
  }
  break;
case "capabilities":
  exec("smartctl -c /dev/$port|awk 'NR>5'",$output);
  $row = ["","",""];
  foreach ($output as $line) {
    if (!$line) continue;
    $line = preg_replace('/^_/','__',preg_replace(array('/__+/','/_ +_/'),'_',str_replace(array(chr(9),')','('),'_',$line)));
    $info = array_map('trim', explode('_', preg_replace('/_( +)_ /','__',$line), 3));
    if (isset($info[0])) $row[0] .= ($row[0] ? " " : "").$info[0];
    if (isset($info[1])) $row[1] .= ($row[1] ? " " : "").$info[1];
    if (isset($info[2])) $row[2] .= ($row[2] ? " " : "").$info[2];
    if (substr($row[2],-1)=='.') {
      echo "<tr><td>{$row[0]}</td><td>{$row[1]}</td><td>{$row[2]}</td></tr>";
      $row = ["","",""];
    }
  }
  break;
case "identify":
  exec("smartctl -i /dev/$port|awk 'NR>4'",$output);
  exec("smartctl -H /dev/$port|grep 'result'|sed 's:self-assessment test result::'",$output);
  foreach ($output as $line) {
    if (!strlen($line)) continue;
    list($title,$info) = array_map('trim', explode(':', $line, 2));
    if ($info=='PASSED') $info = "<span class='green-text'>Passed</span>";
    if ($info=='FAILED') $info = "<span class='red-text'>Failed</span>";
    echo "<tr><td>".preg_replace("/ is$/","",$title).":</td><td>$info</td></tr>";
  }
  break;
case "save":
  exec("smartctl -a /dev/$port >{$_SERVER['DOCUMENT_ROOT']}/{$_POST['file']}");
  break;
case "delete":
  @unlink("/var/tmp/{$_POST['file']}");
  break;
case "short":
  spindownDelay($port);
  exec("smartctl -t short /dev/$port");
  break;
case "long":
  spindownDelay($port);
  exec("smartctl -t long /dev/$port");
  break;
case "stop":
  exec("smartctl -X /dev/$port");
  break;
case "update":
  if (!exec("hdparm -C /dev/$port|grep -om1 active")) {
    $cmd = $_POST['type']=='New' ? "cmd=/webGui/scripts/hd_parm&arg1=up&arg2={$_POST['name']}" : "cmdSpinup={$_POST['name']}";
    echo "<a href='/update.htm?$cmd' class='info' target='progressFrame'><input type='button' value='Spin Up'></a><span class='orange-text'><big>Unavailable - disk must be spun up</big></span>";
    break;
  }
  $progress = exec("smartctl -c /dev/$port|grep -Pom1 '\d+%'");
  if ($progress) {
    echo "<big><i class='fa fa-spinner fa-pulse'></i> self-test in progress, ".(100-substr($progress,0,-1))."% complete</big>";
    break;
  }
  $result = trim(exec("smartctl -l selftest /dev/$port|grep -m1 '^# 1'|cut -c26-55"));
  if (!$result) {
    echo "<big>No self-tests logged on this disk</big>";
    break;
  }
  if (strpos($result, "Completed without error")!==false) {
    echo "<span class='green-text'><big>$result</big></span>";
    break;
  }
  if (strpos($result, "Aborted")!==false or strpos($result, "Interrupted")!==false) {
    echo "<span class='orange-text'><big>$result</big></span>";
    break;
  }
  echo "<span class='red-text'><big>Errors occurred - Check SMART report</big></span>";
  break;
case "selftest":
  echo shell_exec("smartctl -l selftest /dev/$port|awk 'NR>5'");
  break;
case "errorlog":
  echo shell_exec("smartctl -l error /dev/$port|awk 'NR>5'");
  break;
}
?>

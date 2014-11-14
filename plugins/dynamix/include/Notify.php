<?PHP
/* Copyright 2012, Andrew Hamer-Adams, http://www.pixeleyes.co.nz.
 * Copyright 2014, Bergware International.
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
extract(parse_plugin_cfg("dynamix",true));

$unread = "{$notify['path']}/unread";
$archive = "{$notify['path']}/archive";

switch ($_POST['cmd']) {
case 'init':
  @mkdir($unread,0755,true);
  @mkdir($archive,0755,true);
  $files = glob("$unread/*.notify"); 
  foreach ($files as $file) if (!is_readable($file)) chmod($file,0666);
  break;
case 'add':
  $event = 'unRAID Status';
  $subject = 'Notification';
  $description = 'No description';
  $importance = 'normal';
  $timestamp = time();
  $ticket = $timestamp;
  foreach ($_POST as $option => $value) {
    switch ($option) {
    case 'e':
      $event = $value;
    break;
    case 's':
      $subject = $value;
    break;
    case 'd':
      $description = $value;
    break;
    case 'i':
      $importance = $value;
    break;
    case 'x':
      $ticket = 'ticket';
    break;}
  }
  $file = "{$unread}/{$event}-{$ticket}.notify";
  file_put_contents($file,"timestamp = $timestamp\nevent = $event\nsubject = $subject\ndescription = $description\nimportance = $importance\n");
  break;
case 'get':
  $output = array();
  $json = array();
  $files = glob("$unread/*.notify"); 
  usort($files, create_function('$a,$b', 'return filemtime($a)-filemtime($b);'));
  $i = 0;
  foreach ($files as $file) {
    if (!is_readable($file)) continue;
    $fields = preg_split('/\n/', file_get_contents($file));
    $time = true;
    foreach ($fields as $field) {
      if (!$field) continue;
      $item = explode('=', $field);
      if ($time) {$item[1] = date("{$notify['date']} {$notify['time']}", $item[1]); $time = false;}
      $output[$i][] = '\"'.trim($item[0]).'\":\"'.trim($item[1]).'\"';
    }
    $output[$i++][] = '\"file\":\"'.basename($file).'\"';
    chmod($file,0000);
  }
  for ($n=0; $n<$i; $n++) $json[] = '"{'.implode(',', $output[$n]).'}"';
  echo '['.implode(',', $json).']';
  break;
case 'archive':
  $open = "$unread/{$_POST['file']}";
  $closed = "$archive/{$_POST['file']}";
  if (file_exists($open)) {rename($open, $closed); chmod($closed,0666);}
  break;}
?>

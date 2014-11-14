<?PHP
/* Copyright 2014, Bergware International
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
$name = $_POST['name'];
switch ($name) {
case 'crontab':
  $pid = exec("crontab -l | grep 'mdcmd'");
  break;
case 'preclear_disk':
  $pid = exec("ps -o pid,command --ppid 1 | awk -F/ '/$name .*{$_POST['device']}$/ {print $1}'");
  break;
case '21':
  $pid = exec("lsof -i:21 -n -P | awk '/\(LISTEN\)/ {print $2}'");
  break;
default:
  $pid = exec("pidof -s -x $name");
  break;
}
if (isset($_POST['update'])) {$span = ""; $_span = "";}
else {$span = "<span id='progress' class='status'>"; $_span = "</span>";}

echo $pid ? "{$span}Status:<span class='green'>Running</span>{$_span}" : "{$span}Status:<span class='orange'>Stopped</span>{$_span}";
?>
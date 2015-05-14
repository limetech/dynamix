<?PHP
/* Copyright 2015, Bergware International.
 * Copyright 2015, Lime Technology
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
switch ($_POST['cmd']) {
case 'save':
  $raw  = empty($_POST['raw']) ? false : $_POST['raw'];
  $file = $_POST['file'];
  $root = $_POST['root'];
  $name = basename($file);
  if ($raw == false) {
    $tmp = "/var/tmp/$name.txt";
    $name .= '.zip';
    exec("todos <$file >$tmp");
    exec("zip -qj $root/$name $tmp");
    unlink($tmp);
  } elseif ($raw == 'windows') {
    $name .= '.rtf';
    exec("todos <$file >$root/$name");
  } elseif ($raw == 'unix') {
    $name .= '.rtf';
    exec("cp -f $file $root/$name");
  }
  echo "/$name";
  break;
case 'delete':
  @unlink($_POST['file']);
  break;
case 'diag':
  $zip = $_POST['file'];
  exec("mkdir -p /diagnostics/array /diagnostics/config /diagnostics/log /diagnostics/shares /diagnostics/smart");
  file_put_contents("/diagnostics/array/array.txt", str_replace("\n","\r\n",print_r(parse_ini_file('/var/local/emhttp/disks.ini',true),true)));
  foreach (glob("/boot/config/*.cfg") as $file) exec("todos <$file >/diagnostics/config/".basename($file,'.cfg').".txt");
  exec("cp /boot/config/super.dat /diagnostics/config");
  exec("todos </boot/config/go >/diagnostics/config/go.txt");
  foreach (glob("/var/log/syslog*") as $file) exec("todos <$file >/diagnostics/log/".basename($file).".txt");
  foreach (glob("/boot/config/shares/*.cfg") as $file) exec("todos <$file >/diagnostics/shares/".basename($file,'.cfg').".txt");
  exec("ls -l /dev/disk/by-id/[au]*|awk '$0!~/-part/{split($11,a,\"/\");print a[3],substr($9,21)}'|sort", $devices);
  foreach ($devices as $device) {
    $disk = explode(' ',$device);
    exec("smartctl -a /dev/${disk[0]} >/diagnostics/smart/${disk[1]}.txt");
  }
  exec("zip -qmr $zip /diagnostics");
  echo "/".basename($zip);
  break;
}
?>

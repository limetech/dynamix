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
  $os  = empty($_POST['os']) ? false : $_POST['os'];
  $file = $_POST['file'];
  $root = $_POST['root'];
  $name = basename($file);
  if ($os == false) {
    $tmp = "/var/tmp/$name.txt";
    $name .= '.zip';
    exec("todos <$file >$tmp");
    exec("zip -qj $root/$name $tmp");
    unlink($tmp);
  } elseif ($os == 'windows') {
    exec("todos <$file >$root/$name");
  } elseif ($os == 'unix') {
    exec("cp -f $file $root/$name");
  }
  echo "/$name";
  break;
case 'delete':
  @unlink($_POST['file']);
  break;
case 'diag':
  $zip = $_POST['file'];
  exec("/usr/local/emhttp/plugins/dynamix/scripts/diagnostics $zip");
  echo "/".basename($zip);
  break;
}
?>

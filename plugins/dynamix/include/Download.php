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
$root = $_POST['root'];
switch ($_POST['cmd']) {
case 'save':
  $log = $_POST['file'];
  $zip = basename($log).'.zip';
  if (isset($_POST['unix'])) {
    exec("zip -qj $root/$zip $log");
  } else {
    $tmp = '/var/tmp/'.basename($log).'.txt';
    exec("todos <$log >$tmp");
    exec("zip -qj $root/$zip $tmp");
    unlink($tmp);
  }
  echo "/$zip";
  break;
case 'del':
  @unlink("$root/{$_POST['zip']}");
  break;}
?>
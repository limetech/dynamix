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
  $os = empty($_POST['os']) ? false : $_POST['os'];
  $file = $_POST['file'];
  $source = $_POST['source'];
  if ($os == false) {
    $tmp = '/var/tmp/'.basename($source).'.txt';
    copy($source, $tmp);
    exec("zip -qlj $file $tmp");
    @unlink($tmp);
  } elseif ($os == 'windows') {
    exec("todos <$source >$file");
  } elseif ($os == 'unix') {
    exec("cp -f $source $file");
  }
  echo "/".basename($file);
  break;
case 'delete':
  $file = $_POST['file'];
  @unlink($file);
  break;
case 'diag':
  $file = $_POST['file'];
  exec("/usr/local/emhttp/plugins/dynamix/scripts/diagnostics $file");
  echo "/".basename($file);
  break;
}
?>

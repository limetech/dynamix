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
$file = $_POST['file'];

switch ($_POST['cmd']) {
case 'save':
  $root = $_POST['root'];
  $zip = basename($file).'.zip';
  if (empty($_POST['unix'])) {
  // Save in windows format (default)
    $tmp = '/var/tmp/'.basename($file).'.txt';
    exec("todos <$file >$tmp");
    exec("zip -qj $root/$zip $tmp");
    unlink($tmp);
  } else {
  // Save in unix format
    exec("zip -qj $root/$zip $file");
  }
  echo "/$zip";
  break;
case 'delete':
  if (isset($_POST['root'])) {
    @unlink("{$_POST['root']}/$file");
  } else {
    @unlink($file);
  }
  break;}
?>
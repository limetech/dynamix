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
$file = $_POST['file'];

switch ($_POST['cmd']) {
case 'save':
  $raw  = empty($_POST['raw']) ? false : $_POST['raw'];
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
  @unlink($file);
  break;}
?>

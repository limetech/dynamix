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
  $file = $_POST['file'];
  $zip = basename($file).'.zip';
  exec("zip -qj $root/$zip $file");
  echo "/$zip";
  break;
case 'del':
  exec("rm -f $root/{$_POST['zip']}");
  break;}
?>
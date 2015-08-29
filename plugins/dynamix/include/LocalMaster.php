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
$lmb = exec("nmblookup -M -- -|grep -Pom1 '^\S+'");
$tag = exec("nmblookup -A $lmb|grep -Pom1 '^\s+\K\S+'");
if (exec("ifconfig|grep -PA1 '^(bond|eth|br)\d+:'|grep -om1 'inet $lmb'")) {
  echo "<i class='fa fa-volume-up icon'></i>";
  if (isset($_GET['smb'])) echo "$tag is current local master browser";
}
if (isset($_GET['smb'])) echo "#$tag &bullet; $lmb";
?>

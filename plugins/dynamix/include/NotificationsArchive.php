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
$files = glob($_POST['log'], GLOB_NOSORT);
usort($files, create_function('$a,$b', 'return filemtime($b)-filemtime($a);'));

foreach ($files as $file) {
  $fields = preg_split('/\n/', file_get_contents($file));
  $c = 0;
  foreach ($fields as $field) {
    if (!$field) continue;
    $item = explode('=', $field);
    if (!$c++) echo "<tr><td>".date("{$_POST['date']} {$_POST['time']}", $item[1])."</td>"; else echo "<td>{$item[1]}</td>";
  }
  echo "<td style='text-align:center'><a href='/webGui/include/DeleteLogFile.php?log=$file' target='progressFrame'><img src='/webGui/images/delete.png' title='Delete notification'></a></td></tr>";
}
if (empty($files)) echo "<tr><td colspan='6' style='text-align:center'><em>No notifications available</em></td></tr>";
?>

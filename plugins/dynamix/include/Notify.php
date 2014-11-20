<?PHP
/* Copyright 2012, Andrew Hamer-Adams, http://www.pixeleyes.co.nz.
 * Copyright 2014, Bergware International.
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
$notify = "/usr/local/emhttp/plugins/dynamix/scripts/notify";
switch ($_POST['cmd']) {
case 'init':
  shell_exec("$notify init");
  break;
case 'add':
  foreach ($_POST as $option => $value) {
    switch ($option) {
     case 'e':
      $notify .= " -e '$value'";
      break;
     case 's':
      $notify .= " -s '$value'";
      $subject = $value;
      break;
     case 'd':
      $notify .= " -e '$value'";
      $description = $value;
      break;
     case 'i':
      $notify .= " -i '$value'";
      $importance = $value;
      break;
     case 'm':
      $notify .= " -m '$value'";
      $importance = $value;
      break;
     case 'x':
      $notify .= " -x";
      break; 
     case 't':
      $notify .= " -t";
      break; 
    }
  }
  shell_exec("$notify add");
  break;
case 'get':
  echo shell_exec("$notify get");
  break;
case 'archive':
  shell_exec("$notify archive '{$_POST['file']}'");
  break;
}
?>

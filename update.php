<?PHP
/* Copyright 2014, Lime Technology LLC.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2, or (at your option)
 * any later version.
 */
/* Program updates made by Bergware International (October 2014) */
?>
<?
/* UPDATE.PHP is used to update selected name=value variables in a configuration file.
 * Note that calling this function will write the configuration file on the flash.
 * The $_POST variable contains a list of key/value parameters to be updated in the file.
 * There are a number of special parameters prefixed with a hash '#' character:
 *
 * #file    : the path+name of the file to be updated. It does not need to previously exist.
 *            the configuration file is always placed under folder '/boot/config'.
 *            this parameter may be omitted to perform a command execution only (see #command).
 * #section : if present, then the ini file consists of a set of named sections, and all of the
 *            configuration parameters apply to this one particular section.
 *            if omitted, then it's just a flat ini file without sections.
 * #include : specifies name of an include file to read and execute in before saving the file contents
 * #cleanup : if present then parameters with empty strings are omitted from being written to the file
 * #command : a shell command to execute after updating the configuration file
 */
function write_log($string) {
  echo "<script>addLog(\"{$string}\");</script>";
  syslog(LOG_INFO, $string);
}
// unRAID update control
readfile('update.htm');

$file = isset($_POST['#file']) ? $_POST['#file'] : false;
$command = isset($_POST['#command']) ? $_POST['#command'] : false;

if ($file) {
// prepend with boot (flash) if path is relative
  if ($file[0]!='/') $file = "boot/config/plugins/$file";
  $section = isset($_POST['#section']) ? $_POST['#section'] : false;
  $cleanup = isset($_POST['#cleanup']);

  $keys = @parse_ini_file($file, $section);
// the 'save' switch can be reset by the include file to disallow settings saving
  $save = true;
  if (isset($_POST['#include'])) include $_POST['#include'];
  if ($save) {
    $text = "";
    if ($section) {
      foreach ($_POST as $key => $value) if (substr($key,0,1)!='#') $keys[$section][$key] = $value;
      foreach ($keys as $section => $block) {
        $text .= "[$section]\n";
        foreach ($block as $key => $value) if (strlen($value) || !$cleanup) $text .= "$key=\"$value\"\n";
      }
    } else {
      foreach ($_POST as $key => $value) if (substr($key,0,1)!='#') $keys[$key] = $value;
      foreach ($keys as $key => $value) if (strlen($value) || !$cleanup) $text .= "$key=\"$value\"\n";
    }
    @mkdir(dirname($file));
    file_put_contents($file, $text);
  }
}
if ($command) {
  write_log($command);
  $proc = popen($command, 'r');
  while (!feof($proc)) {
    write_log(fread($proc, 4096));
    @flush();
  }
}
?>
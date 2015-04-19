<?PHP
/* Copyright 2014, Lime Technology
 * Copyright 2014, Bergware International.
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
/* UPDATE.PHP is used to update selected name=value variables in a configuration file.
 * Note that calling this function will write the configuration file on the flash.
 * The $_POST variable contains a list of key/value parameters to be updated in the file.
 * There are a number of special parameters prefixed with a hash '#' character:
 *
 * #file    : the pathname of the file to be updated. It does not need to previously exist.
 *            If pathname is relative (no leading '/'), the configuration file will placed
 *            placed under '/boot/config/plugins'.
 *            This parameter may be omitted to perform a command execution only (see #command).
 * #section : if present, then the ini file consists of a set of named sections, and all of the
 *            configuration parameters apply to this one particular section.
 *            if omitted, then it's just a flat ini file without sections.
 * #include : specifies name of an include file to read and execute in before saving the file contents
 * #cleanup : if present then parameters with empty strings are omitted from being written to the file
 * #command : a shell command to execute after updating the configuration file
 * #arg     : an array of arguments for the shell command
 */
function write_log($string) {
  if (empty($string)) {
    return;
  }
  syslog(LOG_INFO, $string);
  $string = str_replace("\n", "<br>", $string);
  $string = str_replace('"', "\\\"", trim($string));
  echo "<script>addLog(\"{$string}\");</script>";
  @flush();
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
      foreach ($_POST as $key => $value) if ($key[0]!='#') $keys[$section][$key] = $value;
      foreach ($keys as $section => $block) {
        $text .= "[$section]\n";
        foreach ($block as $key => $value) if (strlen($value) || !$cleanup) $text .= "$key=\"$value\"\n";
      }
    } else {
      foreach ($_POST as $key => $value) if ($key[0]!='#') $keys[$key] = $value;
      foreach ($keys as $key => $value) if (strlen($value) || !$cleanup) $text .= "$key=\"$value\"\n";
    }
    @mkdir(dirname($file));
    file_put_contents($file, $text);
  }
}
if ($command) {
  if (isset($_POST['#env'])) {
    $envs = $_POST['#env'];
    foreach ($envs as $env) {
      putenv($env);
    }
  }
  if($_POST['env']) foreach (explode("|",$_POST['env']) as $env) putenv($env);
  if (substr($command,0,1) != "/") $command = "{$_SERVER['DOCUMENT_ROOT']}/$command";
  if (isset($_POST['#arg'])) {
    $args = $_POST['#arg'];
    ksort($args);
    $command = "$command " . implode(" ", $args);
  }
  write_log($command);
  $proc = popen($command, 'r');
  while (!feof($proc)) {
    write_log(fgets($proc));
//    write_log(fread($proc, 4096));
//    @flush();
  }
}
?>

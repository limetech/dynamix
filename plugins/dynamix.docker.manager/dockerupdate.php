#!/usr/bin/php
<?
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/dockerClient.php");
$DockerUpdate = new DockerUpdate();
$DockerTemplates = new DockerTemplates();

$verbose = ($argv[1] == "-v") ? TRUE : FALSE;

echo " Updating templates... ";
$out = $DockerTemplates->downloadTemplates();
if ($verbose) foreach ($out as $value) echo "\n$value";

echo " Updating info... ";
$out = $DockerTemplates->getAllInfo(TRUE);
if ($verbose) {
  echo "\n\nUpdating info... ";
  foreach ($out as $key => $value){
    echo "\n$key" ;
    foreach ($value as $k => $v){
      printf("\n   %-10s: %s", $k, $v);
    }
    echo "\n";
  }
}

echo " Done. ";
?>

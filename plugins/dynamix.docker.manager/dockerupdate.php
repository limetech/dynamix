#!/usr/bin/php
<?
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/dockerClient.php");
$DockerUpdate = new DockerUpdate();
$DockerTemplates = new DockerTemplates();

$verbose = ($argv[1] == "-v") ? TRUE : FALSE;

echo " Updating templates... ";
$DockerTemplates->downloadTemplates();
echo " Updating info... ";
$DockerTemplates->getAllInfo(TRUE);
echo " Done. ";
?>

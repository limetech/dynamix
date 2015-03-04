#!/usr/bin/php
<?
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/dockerClient.php");
$DockerTemplates = new DockerTemplates();

if ($argv[1] == "-v") $DockerTemplates->verbose = TRUE;

echo " Updating templates... ";
$DockerTemplates->downloadTemplates();

echo " Updating info... ";
$DockerTemplates->getAllInfo(TRUE);

echo " Done. ";
?>

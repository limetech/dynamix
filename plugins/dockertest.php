#!/usr/bin/php
<?
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
$DockerClient = new DockerClient();
$DockerUpdate = new DockerUpdate();
$DockerTemplates = new DockerTemplates();

$id =  $DockerClient->getContainerID("CrashPlan");
// print_r( $DockerClient->stopContainer("CrashPlan") );

// print_r( $DockerClient->startContainer("CrashPlan") );

print_r( $DockerClient->getContainerDetails($id));
function hex_dump($data, $newline="\n")
{
  static $from = '';
  static $to = '';

  static $width = 16; # number of bytes per line

  static $pad = '.'; # padding for non-visible characters

  if ($from==='')
  {
    for ($i=0; $i<=0xFF; $i++)
    {
      $from .= chr($i);
      $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
    }
  }

  $hex = str_split(bin2hex($data), $width*2);
  $chars = str_split(strtr($data, $from, $to), $width);

  $offset = 0;
  foreach ($hex as $i => $line)
  {
    echo sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;
    $offset += $width;
  }
}

$zz = function($m){
  hex_dump($m);
  print_r(array_map('dechex', array_map('ord', str_split($m))));;
};
// $DockerClient->getDockerJSON("/containers/${id}/logs?stderr=1&stdout=1&tail=350", "GET", $code, $zz);

// $pullecho = function($m){echo $m;};
$id =  $DockerClient->getImageID("linuxserver/sonarr");
// $a = $DockerClient->removeImage($id);

// $a = $DockerClient->getBaseImage2($id);
// $DockerClient->getImageHistory($id);
// GET /containers/json?all=1&filters={%22status%22:[%22exited%22]}
var_dump($DockerClient->getDockerJSON("/containers/json?all=1&filters={%22label%22:[true]}"));

?>
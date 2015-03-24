#!/usr/bin/php
<?
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/dockerClient.php");
$DockerTemplates = new DockerTemplates();

switch ($argv[1]) {
  case '-v':
    $DockerTemplates->verbose = TRUE;
    break;
  case 'check':
    $check = TRUE;
    break;
}

echo isset($check) ? "": " Updating templates... ";
$DockerTemplates->downloadTemplates();

echo isset($check) ? "": " Updating info... ";
$DockerTemplates->getAllInfo(TRUE);

echo isset($check) ? "": " Done. ";

if (isset($check)) { 
  require_once("/usr/local/emhttp/webGui/include/Wrappers.php");
  $DockerClient = new DockerClient();
  $notify = "/usr/local/sbin/notify";
  $unraid = parse_plugin_cfg("dynamix",true);
  $server = strtoupper($var['NAME']);
  $output = $unraid['notify']['docker_notify'];
  
  $all_containers = $DockerClient->getDockerContainers();
  $info = $DockerTemplates->getAllInfo();
  foreach($all_containers as $ct){
    $name           = $ct["Name"];
    $hasUpdate   = ($info[$name]['updated'] == "false") ? TRUE : FALSE;
    if ($hasUpdate) {
      exec("$notify -e 'Docker Manager' -s 'Notice [$server] - $name: new version available' -d 'A new version of $name is available.' -i 'normal $output' -x");
    }
  }
}

exit(0);

?>

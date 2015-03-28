#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/dockerClient.php");

$docker = new DockerTemplates();

foreach ($argv as $arg) {
  switch ($arg) {
  case '-v'   : $docker->verbose = true; break;
  case 'check': $check = true; break;}
}

if (!isset($check)) {
  echo " Updating templates... ";
  $docker->downloadTemplates();
  echo " Updating info... ";
  $docker->getAllInfo(true);
  echo " Done.";
} else {
  require_once("/usr/local/emhttp/webGui/include/Wrappers.php");
  $client = new DockerClient();
  $update = new DockerUpdate();
  $notify = "/usr/local/sbin/notify";
  $unraid = parse_plugin_cfg("dynamix",true);
  $server = strtoupper($var['NAME']);
  $output = $unraid['notify']['docker_notify'];

  $list = $client->getDockerContainers();
  $info = $docker->getAllInfo();
  foreach ($list as $ct) {
    $name = $ct['Name'];
    $image = $ct['Image'];
    if ($info[$name]['updated'] == "false") {
      $new = $update->getRemoteVersion($docker->getTemplateValue($image, "Registry"), $image);
      exec("$notify -e 'Docker - $name [$new]' -s 'Notice [$server] - Docker update $new' -d 'A new version of $name is available' -i 'normal $output' -x");
    }
  }
}
exit(0);
?>

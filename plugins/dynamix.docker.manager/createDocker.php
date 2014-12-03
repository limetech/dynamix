<?
ignore_user_abort(true);
include_once("/usr/local/emhttp/plugins/dynamix.docker.manager/dockerClient.php");
$DockerClient = new DockerClient();
$DockerUpdate = new DockerUpdate();
$DockerTemplates = new DockerTemplates();

function prepareDir($dir){
  if (strlen($dir)){
    if ( ! is_dir($dir) && ! is_file($dir)){
      mkdir($dir, 0777, true);
      chown($dir, 'nobody');
      chgrp($dir, 'users');
      sleep(1);
    }
  }
}

function ContainerExist($container){
  global $DockerClient;

  $all_containers = $DockerClient->getDockerContainers();
  if ( ! $all_containers) { return FALSE; }
  foreach ($all_containers as $ct) {
    if ($ct['Name'] == $container){
      return True;
      break;
    }
  }
  return False;
}

function trimLine($text){
  return preg_replace("/([\n^])[\s]+/", '$1', $text);
}

function pullImage($image) {
  readfile("/usr/local/emhttp/plugins/dynamix.docker.manager/log.htm");
  echo "<script>addLog('<fieldset style=\"margin-top:1px;\" class=\"CMD\"><legend>Pulling image: " . $image . "</legend><p class=\"logLine\" id=\"logBody\"></p></fieldset>');</script>";
  @flush();

  $fp = stream_socket_client('unix:///var/run/docker.sock', $errno, $errstr);
  if ($fp === false) {
    echo "Couldn't create socket: [$errno] $errstr";
    return NULL;
  }
  $out="POST /images/create?fromImage=$image HTTP/1.1\r\nConnection: Close\r\n\r\n";
  fwrite($fp, $out);
  $cid = "";
  $cstatus="";
  $gtotal = 0;
  while (!feof($fp)) {
    $cnt =  json_decode( fgets($fp, 5000), TRUE );
    $id = ( isset( $cnt['id'] )) ? $cnt['id'] : "";
    if ($id != $cid && strlen($id)){
      $cid = $id;
      $cstatus = "";
      echo "<script>addLog('IMAGE ID: " . $id ."');</script>";
      @flush();
    }
    $status = ( isset( $cnt['status'] )) ? $cnt['status'] : "";
    if ($status != $cstatus && strlen($status)){
      $cstatus = $status;
      if ( isset($cnt['progressDetail']['total']) ) $gtotal += $cnt['progressDetail']['total'];
      echo "<script>addLog('STATUS: " . $status . " <span class=\"progress\"></span>');</script>\n";
      @flush();
    }
    if ($status == "Downloading"){
      $total = $cnt['progressDetail']['total'];
      $current = $cnt['progressDetail']['current'];
      $percentage = round(($current/$total) * 100);
      echo "<script>show_Prog(' " . $percentage . "% of " . ceil($total/1024/1024) . "MB');</script>\n";
      @flush();
    }
  }
  echo "<script>addLog('<br><b>TOTAL DATA PULLED:</b> " . ceil($gtotal/1024/1024) . " MB<span class=\"progress\"></span>');</script>\n";
}

function xmlToCommand($xmlFile){
  global $var;
    $doc = new DOMDocument();
    $doc->loadXML($xmlFile);

  $Name          = $doc->getElementsByTagName( "Name" )->item(0)->nodeValue;
  $cmdName       = (strlen($Name)) ? '--name="' . $Name . '"' : "";
  $Privileged    = $doc->getElementsByTagName( "Privileged" )->item(0)->nodeValue;
  $cmdPrivileged = (strtolower($Privileged) == 'true') ?  '--privileged="true"' : "";
  $Repository    = $doc->getElementsByTagName( "Repository" )->item(0)->nodeValue;
  $Mode          = $doc->getElementsByTagName( "Mode" )->item(0)->nodeValue;
  $cmdMode       = '--net="'.strtolower($Mode).'"';
  $BindTime      = $doc->getElementsByTagName( "BindTime" )->item(0)->nodeValue;
  // $cmdBindTime   = (strtolower($BindTime) == "true") ? '"/etc/localtime":"/etc/localtime":ro' : '';
  $cmdBindTime   = (strtolower($BindTime) == "true") ? 'TZ="' . $var['timeZone'] . '"' : '';

  $Ports = array('');
  foreach($doc->getElementsByTagName('Port') as $port){
    $ContainerPort = $port->getElementsByTagName( "ContainerPort" )->item(0)->nodeValue;
    if (! strlen($ContainerPort)){ continue; }
    $HostPort      = $port->getElementsByTagName( "HostPort" )->item(0)->nodeValue;
    $Protocol      = $port->getElementsByTagName( "Protocol" )->item(0)->nodeValue;
    $Ports[]       = sprintf("%s:%s/%s", $HostPort, $ContainerPort, $Protocol);
  }

  $Volumes = array('');
  foreach($doc->getElementsByTagName('Volume') as $volume){
    $ContainerDir = $volume->getElementsByTagName( "ContainerDir" )->item(0)->nodeValue;
    if (! strlen($ContainerDir)){ continue; }
    $HostDir      = $volume->getElementsByTagName( "HostDir" )->item(0)->nodeValue;
    $DirMode      = $volume->getElementsByTagName( "Mode" )->item(0)->nodeValue;
    $Volumes[]    = sprintf( '"%s":"%s":%s', $HostDir, $ContainerDir, $DirMode);
  }

  // if (strlen($cmdBindTime)) {
  //   $Volumes[] = $cmdBindTime;
  // }

  $Variables = array('');
  foreach($doc->getElementsByTagName('Variable') as $variable){
    $VariableName  = $variable->getElementsByTagName( "Name" )->item(0)->nodeValue;
    if (! strlen($VariableName)){ continue; }
    $VariableValue = $variable->getElementsByTagName( "Value" )->item(0)->nodeValue;
    $Variables[]   = sprintf('%s="%s"', $VariableName, $VariableValue);
  }

  if (strlen($cmdBindTime)) {
    $Variables[] = $cmdBindTime;
  }

  $cmd = sprintf('/usr/bin/docker run -d %s %s %s %s %s %s %s', $cmdName, $cmdMode, $cmdPrivileged, implode(' -e ', $Variables),
       implode(' -p ', $Ports), implode(' -v ', $Volumes), $Repository);
  $cmd = preg_replace('/\s+/', ' ', $cmd);

  return array($cmd, $Name, $Repository);
}

function addElement($doc, $el, $elName, $elVal){
  $node = $el->appendChild($doc->createElement($elName));
  $node->appendChild($doc->createTextNode(addslashes($elVal)));
  return $node;
}

function postToXML($post, $setOwnership = FALSE){
  global $DockerUpdate;
  $doc = new DOMDocument('1.0', 'utf-8');
  $doc->preserveWhiteSpace = false;
  $doc->formatOutput = true;
  $root = $doc->createElement('Container');
  $root = $doc->appendChild($root);

  $docName       = $root->appendChild($doc->createElement('Name'));
  if ( isset( $post[ 'Description' ] )) addElement($doc, $root, 'Description', $post[ 'Description' ]);
  if ( isset( $post[ 'Registry' ] ))    addElement($doc, $root, 'Registry', $post[ 'Registry' ]);
  $docRepository = $root->appendChild($doc->createElement('Repository'));
  $BindTime      = $root->appendChild($doc->createElement('BindTime'));
  $Privileged    = $root->appendChild($doc->createElement('Privileged'));
  $Environment   = $root->appendChild($doc->createElement('Environment'));
  $docNetworking = $root->appendChild($doc->createElement('Networking'));
  $Data          = $root->appendChild($doc->createElement('Data'));
  $Version       = $root->appendChild($doc->createElement('Version'));
  $Mode          = $docNetworking->appendChild($doc->createElement('Mode'));
  $Publish       = $docNetworking->appendChild($doc->createElement('Publish'));
  $Name          = preg_replace('/\s+/', '', $post["containerName"]);

  // Editor Values
  if ( isset( $post[ 'WebUI' ] ))  addElement($doc, $root, 'WebUI', $post[ 'WebUI' ]);
  if ( isset( $post[ 'Banner' ] )) addElement($doc, $root, 'Banner', $post[ 'Banner' ]);
  if ( isset( $post[ 'Icon' ] ))   addElement($doc, $root, 'Icon', $post[ 'Icon' ]);

  $docName->appendChild($doc->createTextNode(addslashes($Name)));
  $docRepository->appendChild($doc->createTextNode(addslashes($post["Repository"])));
  $BindTime->appendChild($doc->createTextNode((strtolower($post["BindTime"])     == 'on') ? 'true' : 'false'));
  $Privileged->appendChild($doc->createTextNode((strtolower($post["Privileged"]) == 'on') ? 'true' : 'false'));
  $Mode->appendChild($doc->createTextNode(strtolower($post["NetworkType"])));

    for ($i = 0; $i < count($post["hostPort"]); $i++){
      if (! strlen($post["containerPort"][$i])) { continue;}
    $protocol      = $post["portProtocol"][$i];
    $Port          = $Publish->appendChild($doc->createElement('Port'));
    $HostPort      = $Port->appendChild($doc->createElement('HostPort'));
    $ContainerPort = $Port->appendChild($doc->createElement('ContainerPort'));
    $Protocol      = $Port->appendChild($doc->createElement('Protocol'));
      $HostPort->appendChild($doc->createTextNode(trim($post["hostPort"][$i])));
      $ContainerPort->appendChild($doc->createTextNode($post["containerPort"][$i]));
      $Protocol->appendChild($doc->createTextNode($protocol));
    };

    for ($i = 0; $i < count($post["VariableName"]); $i++){
      if (! strlen($post["VariableName"][$i])) { continue;}
    $Variable      = $Environment->appendChild($doc->createElement('Variable'));
    $VariableName  = $Variable->appendChild($doc->createElement('Name'));
    $VariableValue = $Variable->appendChild($doc->createElement('Value'));
      $VariableName->appendChild($doc->createTextNode(addslashes(trim($post["VariableName"][$i]))));
      $VariableValue->appendChild($doc->createTextNode(addslashes(trim($post["VariableValue"][$i]))));
    }

    for ($i = 0; $i < count($post["hostPath"]); $i++){
      if (! strlen($post["hostPath"][$i])) {continue; }
      if (! strlen($post["containerPath"][$i])) {continue; }
      $tmpMode = $post["hostWritable"][$i];
      if ($setOwnership){
        prepareDir($post["hostPath"][$i]);
      }
    $Volume       = $Data->appendChild($doc->createElement('Volume'));
    $HostDir      = $Volume->appendChild($doc->createElement('HostDir'));
    $ContainerDir = $Volume->appendChild($doc->createElement('ContainerDir'));
    $DirMode      = $Volume->appendChild($doc->createElement('Mode'));
    $HostDir->appendChild($doc->createTextNode(addslashes($post["hostPath"][$i])));
    $ContainerDir->appendChild($doc->createTextNode(addslashes($post["containerPath"][$i])));
    $DirMode->appendChild($doc->createTextNode($tmpMode));
    }

    $currentVersion = $DockerUpdate->getRemoteVersion($post["Registry"]);
    $Version->appendChild($doc->createTextNode($currentVersion));

    return $doc->saveXML();
}

if ($_POST){

  $postXML = postToXML($_POST, TRUE);

  // Get the command line
  list($cmd, $Name, $Repository) = xmlToCommand($postXML);

  // Saving the generated configuration file.
  $userTmplDir = $dockerManPaths['templates-user'];
  if(is_dir($userTmplDir) === FALSE){
    mkdir($userTmplDir, 0777, true);
  }

  if(strlen($Name)) {
    $filename = sprintf('%s/my-%s.xml', $userTmplDir, $Name);
    file_put_contents($filename, $postXML);
  }

  // Pull image
  pullImage($Repository);

  // Remove existing container
  if (ContainerExist($Name)){
    $_GET['cmd'] = "/usr/bin/docker rm -f $Name";
    include($dockerManPaths['plugin'] . "/exec.php");
  }

  // Injecting the command in $_GET variable and executing.
  $_GET['cmd'] = $cmd;
  include($dockerManPaths['plugin'] . "/exec.php");
  $DockerTemplates->removeInfo($Name);

  die();
}


if ($_GET['updateContainer']){
  foreach ($_GET['ct'] as $value) {
    $Name = urldecode($value);
    $tmpl = $DockerTemplates->getUserTemplate($Name);

    if (! $tmpl){
      echo 'Configuration not found. Was this container created using this plugin?';
      continue;
    }

    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->preserveWhiteSpace = false;
    $doc->load( $tmpl );
    $doc->formatOutput = TRUE;

    $Repository = $doc->getElementsByTagName( "Repository" )->item(0)->nodeValue;
    $Registry = $doc->getElementsByTagName( "Registry" )->item(0)->nodeValue;

    $CurrentVersion = $DockerUpdate->getRemoteVersion($Registry);

    if ($CurrentVersion){
      if ( $doc->getElementsByTagName( "Version" )->length == 0 ) {
        $root    = $doc->getElementsByTagName( "Container" )->item(0);
        $Version = $root->appendChild($doc->createElement('Version'));
      } else {
        $Version = $doc->getElementsByTagName( "Version" )->item(0);
      }
      $Version->nodeValue = $CurrentVersion;

      file_put_contents($tmpl, $doc->saveXML());
    }

    $oldContainerID = $DockerClient->getImageID($Repository);
    list($cmd, $Name, $Repository) = xmlToCommand($doc->saveXML());

    // Pull image
    flush();
    pullImage($Repository);

    $_GET['cmd'] = "/usr/bin/docker rm -f $Name";
    include($dockerManPaths['plugin'] . "/exec.php");

    $_GET['cmd'] = $cmd;
    include($dockerManPaths['plugin'] . "/exec.php");

    $DockerTemplates->removeInfo($Name);
    $newContainerID = $DockerClient->getImageID($Repository);
    if ( $oldContainerID and $oldContainerID != $newContainerID){
      $_GET['cmd'] = sprintf("/usr/bin/docker rmi %s", $oldContainerID);
      include($dockerManPaths['plugin'] . "/exec.php");
    }

    $DockerTemplates->removeInfo($Name);
  }
  die();
}


if($_GET['rmTemplate']){
  unlink($_GET['rmTemplate']);
}

if($_GET['xmlTemplate']){
  list($xmlType, $xmlTemplate) = split(':', urldecode($_GET['xmlTemplate']));
  if(is_file($xmlTemplate)){
    $doc = new DOMDocument();
    $doc->load($xmlTemplate);

    $templateRepository   = $doc->getElementsByTagName( "Repository" )->item(0)->nodeValue;
    $templateName         = $doc->getElementsByTagName( "Name" )->item(0)->nodeValue;
    $Registry             = $doc->getElementsByTagName( "Registry" )->item(0)->nodeValue;
    $templatePrivileged   = (strtolower($doc->getElementsByTagName( "Privileged" )->item(0)->nodeValue) == 'true') ? 'checked' : "";
    $templateMode         = $doc->getElementsByTagName( "Mode" )->item(0)->nodeValue;;
    $readonly             = ($xmlType == 'default') ? 'readonly="readonly"' : '';
    $disabled             = ($xmlType == 'default') ? 'disabled="disabled"' : '';

    if ( $doc->getElementsByTagName( "Description" )->length > 0 ) {
      $templateDescription = $doc->getElementsByTagName( "Description" )->item(0)->nodeValue;
    } else {
      $templateDescription = $DockerTemplates->getTemplateValue($templateRepository, "Description", "default");
    }

    if ( $doc->getElementsByTagName( "Registry" )->length > 0 ) {
      $templateRegistry = $doc->getElementsByTagName( "Registry" )->item(0)->nodeValue;
    } else {
      $templateRegistry = $DockerTemplates->getTemplateValue($templateRepository, "Registry", "default");
    }

    if ( $doc->getElementsByTagName( "WebUI" )->length > 0 ) {
      $templateWebUI = $doc->getElementsByTagName( "WebUI" )->item(0)->nodeValue;
    } else {
      $templateWebUI = $DockerTemplates->getTemplateValue($templateRepository, "WebUI", "default");
    }

    if ( $doc->getElementsByTagName( "Banner" )->length > 0 ) {
      $templateBanner = $doc->getElementsByTagName( "Banner" )->item(0)->nodeValue;
    } else {
      $templateBanner = $DockerTemplates->getTemplateValue($templateRepository, "Banner", "default");
    }

    if ( $doc->getElementsByTagName( "Icon" )->length > 0 ) {
      $templateIcon = $doc->getElementsByTagName( "Icon" )->item(0)->nodeValue;
    } else {
      $templateIcon = $DockerTemplates->getTemplateValue($templateRepository, "Icon", "default");
    }

    $templateDescription = stripslashes($templateDescription);
    $templateRegistry    = stripslashes($templateRegistry);
    $templateWebUI       = stripslashes($templateWebUI);
    $templateBanner      = stripslashes($templateBanner);
    $templateIcon        = stripslashes($templateIcon);

    $templateDescBox      = preg_replace('/\[/', '<', $templateDescription);
    $templateDescBox      = preg_replace('/\]/', '>', $templateDescBox);

    $templatePorts = '';
    $row = '
    <tr id="portNum%s">
      <td>
        <input type="text" name="containerPort[]" value="%s" class="textPort" %s title="Set the port your app uses inside the container.">
      </td>
      <td>
        <input type="text" name="hostPort[]" value="%s" class="textPort" title="Set the port you use to interact with the app.">
      </td>
      <td>
        <select name="portProtocol[]">
          <option value="tcp">tcp</option>
          <option value="udp" %s>udp</option>
        </select>
      </td>
      <td>
        <input type="button" value="Remove" onclick="removePort(%s);" %s>
      </td>
    </tr>';

    $i = 1;
    foreach($doc->getElementsByTagName('Port') as $port){
      $j = $i + 100;
      $ContainerPort  = $port->getElementsByTagName( "ContainerPort" )->item(0)->nodeValue;
      if (! strlen($ContainerPort)){ continue; }
      $HostPort       = $port->getElementsByTagName( "HostPort" )->item(0)->nodeValue;
      $Protocol       = $port->getElementsByTagName( "Protocol" )->item(0)->nodeValue;
      $select = ($Protocol == 'udp') ? 'selected' : '';
      $templatePorts .= sprintf($row, $j, $ContainerPort, $readonly, $HostPort, $select, $j, $disabled);
      $i++;
    }

    $templateVolumes = '';
    $row = '
    <tr id="pathNum%s">
      <td>
        <input type="text" name="containerPath[]" value="%s" class="textPath" onclick="hideBrowser(%s);" %s title="The directory your app uses inside the container. Ex: /config">
      </td>
      <td>
        <input type="text" id="hostPath%s" name="hostPath[]" value="%s" class="textPath" onclick="toggleBrowser(%s);" title="The directory in your array the app have access to. Ex: /mnt/user/Movies"/>
        <div id="fileTree%s" class="fileTree"></div>
      </td>
      <td>
        <select name="hostWritable[]">
          <option value="rw">Read/Write</option>
          <option value="ro" %s>Read Only</option>
        </select>
      </td>
      <td>
        <input type="button" value="Remove" onclick="removePath(%s);" %s />
      </td>
    </tr>';

    $i = 1;
    foreach($doc->getElementsByTagName('Volume') as $volume){
      $j = $i + 100;
      $ContainerDir     = $volume->getElementsByTagName( "ContainerDir" )->item(0)->nodeValue;
      if (! strlen($ContainerDir)){ continue; }
      $HostDir          = $volume->getElementsByTagName( "HostDir" )->item(0)->nodeValue;
      $Mode             = $volume->getElementsByTagName( "Mode" )->item(0)->nodeValue;
      $Mode             = ($Mode == "ro") ? "selected" : '';
      $templateVolumes .= sprintf($row, $j, $ContainerDir, $j, $readonly, $j, $HostDir, $j, $j, $Mode, $j, $disabled);
      $i++;
    }

    $templateVariables = '';
    $row = '
    <tr id="varNum%s">
      <td>
        <input type="text" name="VariableName[]" value="%s" class="textEnv" %s/>
      </td>
      <td>
        <input type="text" name="VariableValue[]" value="%s" class="textEnv">
        <input type="button" value="Remove" onclick="removeEnv(%s);" %s>
      </td>
    </tr>';

    $i = 1;
    foreach($doc->getElementsByTagName('Variable') as $variable){
      $j = $i + 100;
      $VariableName       = $variable->getElementsByTagName( "Name" )->item(0)->nodeValue;
      if (! strlen($VariableName)){ continue; }
      $VariableValue      = $variable->getElementsByTagName( "Value" )->item(0)->nodeValue;
      $templateVariables .= sprintf($row, $j, $VariableName, $readonly, $VariableValue, $j, $disabled);
      $i++;
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title></title>
</head>
<body>
<link type="text/css" rel="stylesheet" href="/plugins/dynamix.docker.manager/assets/font-awesome-4.2.0/css/font-awesome.min.css">
<link type="text/css" rel="stylesheet" href="/plugins/dynamix.docker.manager/assets/jsFileTree/jqueryFileTree.css" media="screen">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-fonts.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-white.css">

<script type="text/javascript" src="/webGui/scripts/dynamix.js"></script>
<script type="text/javascript" src="/plugins/dynamix.docker.manager/assets/jsFileTree/jqueryFileTree.js"></script>
<script type="text/javascript" src="/plugins/dynamix.docker.manager/assets/addDocker.js"> </script>

<style type="text/css">
  body {
    width: 780px;
    margin: 10px;
    font-size: 14px;
  }
  .fileTree {
    width: 240px;
    height: 150px;
    border-top: solid 1px #BBB;
    border-left: solid 1px #BBB;
    border-bottom: solid 1px #BBB;
    border-right: solid 1px #BBB;
    background: #FFF;
    overflow: scroll;
    padding: 5px;
    position:absolute;
    z-index:100;
    display:none;
  };
  .canvas{
    background: #ffffff;
    width: 100%;
    height: 100%;
  }
  option.list{
    padding: 0px 0px 0px 7px;
  }
  option.bold{
    font-weight:bold;
    font-size: 12px;
  }
  option.title{
    background-color: #625D5D;
    color:#FFFFFF;
    text-align: center;
  }
  input.textPath{
    width: 240px;
  }
  input.textTemplate{
    width: 555px;
  }
  #Template {
    display: block;
  }
  input.textEnv{
    width: 230px;
  }
  input.textPort{
    width: 100px;
  }
  table.pathTab{
    width: 700px;
  }
  table.portRows{
    width: 400px;
  }
  table.envTab{
    width: 620px;
  }
  table.Preferences{
    width: 100%;
  }
  .show {
    display: block;
  }
  table td {
    font-size: 14px;
    vertical-align: bottom;
    text-align: left;
  }
  .desc {
    background: #FFF;
    border: 1px solid #dcdcdc;
    padding: 2px 6px;
    line-height: 20px;
    outline: none;
    -webkit-box-shadow: inset 2px 2px 6px #eef0f0;
    -moz-box-shadow: inset 2px 2px 6px #eef0f0;
    box-shadow: inset 2px 2px 6px #eef0f0;
    margin-top:0;
    margin-right: 10px;
  }
  .toggleMode {
    cursor: pointer;
    color: #a3a3a3;
    letter-spacing: 0px;
    padding: 0px;
    padding-right: 10px;
    font-family: "Raleway",sans-serif;
    font-size: 12px;
    line-height: 1.3em;
    font-weight: bold;
    margin: 0px;
  }
  .toggleMode:hover,
  .toggleMode:focus,
  .toggleMode:active,
  .toggleMode.active{
    color:#625D5D;
  }
</style>

<form method="GET" id="formTemplate">
  <input type="hidden" id="#xmlTemplate" name="xmlTemplate" value="" />
  <input type="hidden" id="#rmTemplate" name="rmTemplate" value="" />
</form>

<div id="canvas" class="canvas" style="z-index:1;">

  <div id="title">
    <span class="left" style='cursor:pointer;'><img src="/plugins/dynamix.docker.manager/icons/preferences.png" class="icon">Preferences:</span>
  </div>
  <div style="text-align:right;vertical-align:top;position:relative;top:-46px;height:0;">
    <span class="toggleMode" onclick="toggleMode();"><i id="toggleMode" class='fa fa-lg'></i> Advanced Mode</span>
  </div>
  <form method="post" id="createContainer" >
    <table class="Preferences">
      <? if($xmlType != "edit") {?>
      <tr>
        <td style="width: 150px;">Template:</td>
        <td >
          <select id="TemplateSelect" size="1" style="width:255px">
            <option value="" selected>Select a template</option>
            <?
            $rmadd = '';
            $prefix = '';
            $type = "";
            $all_templates = array();
            $all_templates['user'] = $DockerTemplates->getTemplates("user");
            $all_templates['default'] = $DockerTemplates->getTemplates("default");
            foreach ($all_templates as $key => $templates) {
              foreach ( $templates as $value){
                if ($key != $type){
                  $type = $key;
                  if ($type == "default") $title = "Default templates";
                  if ($type == "user") $title = "User defined templates";
                  printf("\t\t\t\t\t\t<option value=''></option><option class='title bold' value=''>[ %s ]</option>\n", $title);
                  if ($value["prefix"] != $prefix && $prefix != ''){
                    $prefix = $value["prefix"];
                    printf("\t\t\t\t\t\t<option class='bold' value=''>[ %s ]</option>\n", $prefix);
                  }
                }
                if ($value["prefix"] != $prefix && $value["prefix"] != ''){
                  $prefix = $value["prefix"];
                  printf("\t\t\t\t\t\t<option value=''></option><option class='bold' value=''>[ %s ]</option>\n", $prefix);
                }
                //$value['name'] = str_replace("my-", '', $value['name']);
                $selected = (isset($xmlTemplate) && $value['path'] == $xmlTemplate) ? ' selected ' : '';
                if (strlen($selected) && $type == 'user' ){ $rmadd = $value['path']; }
                printf("\t\t\t\t\t\t<option class='list' value='%s:%s' {$selected} >%s</option>\n", $type, $value['path'], $value['name']);
              };
            }
            ?>
          </select>
          <? if (strlen($rmadd)) {
            echo "<a onclick=\"rmTemplate('$rmadd');\" style=\"cursor:pointer;\"><img src=\"/plugins/dynamix.docker.manager/assets/images/remove.png\" title=\"$rmadd\" width=\"30px\"></a>";
          };?>
        </td>
      </tr>
      <?};?>
      <?if(isset($templateDescBox) && strlen($templateDescBox) && $xmlType == 'default' ){?>
      <tr>
        <td style="vertical-align: top;">
          Description:
        </td>
        <td>
          <div class="desc">
            <?
            echo $templateDescBox;
            if(isset($Registry)){
              echo "<br><br>Container Page: <a href=\"{$Registry}\" target=\"_blank\">{$Registry}</a>";
            }
            ?>
          </div>
        </td>
      </tr>
      <?};?>
      <tr>
        <td>Name:</td>

        <td><input type="text" name="containerName" class="textPath" value="<? if(isset($templateName)){ echo $templateName;} ?>"></td>
      </tr>

      <tr>
        <td>Repository:</td>

        <td><input type="text" name="Repository" class="textPath" value="<? if(isset($templateRepository)){ echo $templateRepository;} ?>"></td>
      </tr>

      <tr>
        <td>Network type:</td>

        <td><select id="NetworkType" name="NetworkType" size="1">
          <? foreach (array('bridge', 'host', 'none') as $value) {
            $selected = ($templateMode == $value) ? "selected" : "";
            echo "<option value=\"{$value}\" {$selected}>".ucwords($value)."</option>";
          }?>
        </select></td>
      </tr>

      <tr>
        <td>Privileged:</td>

        <td><input type="checkbox" name="Privileged" <?if(isset($templatePrivileged)) {echo $templatePrivileged;}?>></td>
      </tr>

      <tr>
        <td>Bind time:</td>

        <td><input type="checkbox" name="BindTime" checked></td>
      </tr>
    </table>

    <div id="additionalFields" style="display:none">
      <div id="title">
        <span class="left"><img src="/plugins/dynamix.docker.manager/icons/default.png" class="icon">Additional fields:</span>
      </div>
      <table class="Template">
        <tr>
          <td style="width: 150px;">
            Docker Hub URL:
          </td>
          <td>
            <input type="text" name="Registry" class="textTemplate" placeholder="https://registry.hub.docker.com/u/username/image" value="<? if(isset($templateRegistry)){ echo trimLine( $templateRegistry );} ?>"/>
          </td>
        </tr>
        <tr>
          <td style="width: 150px;">
            WebUI:
          </td>
          <td>
            <input type="text" name="WebUI" class="textTemplate"/  placeholder="http://[IP]:[PORT:8080]/" value="<? if(isset($templateWebUI)){ echo trim( $templateWebUI );} ?>">
          </td>
        </tr>
        <tr>
          <td style="width: 150px;">
            Banner:
          </td>
          <td>
            <input type="text" name="Banner" class="textTemplate"  placeholder="http://address.to/banner.png" value="<? if(isset($templateBanner)){ echo trim( $templateBanner );} ?>"/>
          </td>
        </tr>
        <tr>
          <td style="width: 150px;">
            Icon:
          </td>
          <td>
            <input type="text" name="Icon" class="textTemplate" placeholder="http://address.to/icon.png" value="<? if(isset($templateIcon)){ echo trim( $templateIcon );} ?>"/>
          </td>
        </tr>
        <tr>
          <td style="width: 150px; vertical-align: top;">
            Description:
          </td>
          <td>
            <textarea name="Description" rows="10" cols="71" class="desc"><? if(isset($templateDescription)){ echo trimLine($templateDescription);} ?></textarea>
          </td>
        </tr>
      </table>
    </div>

    <div id="title">
      <span class="left"><img src="/plugins/dynamix.docker.manager/icons/paths.png" class="icon">Paths</span>
    </div>

    <table id="pathRows" class="pathTab">
      <thead>
        <tr>
          <td>Container volume:</td>
          <td>Host path:</td>
          <td>Mode:</td>
        </tr>
      </thead>

      <tbody>
        <tr>
          <td>
            <input type="text" id="containerPath1" name="containerPath[]" class="textPath" onfocus="hideBrowser(1);" title="The directory your app uses inside the container. Ex: /config">
          </td>
          <td>
            <input type="text" id="hostPath1" name="hostPath[]" class="textPath" autocomplete="off" onclick="toggleBrowser(1);" title="The directory in your array the app have access to. Ex: /mnt/user/Movies">
            <div id="fileTree1" class="fileTree"></div>
          </td>
          <td><select id="hostWritable1" name="hostWritable[]">
              <option value="rw" selected="selected">Read/Write</option>
              <option value="ro">Read Only</option>
            </select>
          </td>
          <td>
            <input onclick="addPath(this.form);" type="button" value="Add Path" class="btn">
          </td>
        </tr>
        <?if(isset($templateVolumes)){echo $templateVolumes;}?>
      </tbody>
    </table>
    <div id="titlePort">
      <div id="title">
        <span class="left"><img src="/plugins/dynamix.docker.manager/icons/network.png" class="icon">Ports</span>
      </div>

      <table id="portRows" class="portRows">
        <tbody>
          <tr>
            <td>Container port:</td>
            <td>Host port:</td>
            <td>Protocol:</td>
          </tr>

          <tr>
            <td>
              <input type="text" id="containerPort1" name="containerPort[]" class="textPort" title="Set the port your app uses inside the container.">
            </td>
            <td>
              <input type="text" id="hostPort1" name="hostPort[]" class="textPort" title="Set the port you use to interact with the app.">
            </td>
            <td>
              <select id="portProtocol1" name="portProtocol[]">
                <option value="tcp" selected="selected">tcp</option>
                <option value="udp">udp</option>
              </select>
            </td>
            <td>
              <input onclick="addPort(this.form);" type="button" value="Add port" class="btn">
            </td>
          </tr>
          <?if(isset($templatePorts)){echo $templatePorts;}?>
        </tbody>
      </table>
    </div>

    <div id="title">
      <span class="left"><img src="/plugins/dynamix.docker.manager/icons/default.png" class="icon">Environment Variables</span>
    </div>

    <table id="envRows" class="envTab">
      <thead>
        <tr>
          <td>Variable Name:</td>
          <td>Variable Value:</td>
        </tr>
      </thead>

      <tbody>
        <tr>
          <td>
            <input type="text" id="VariableName1" name="VariableName[]" class="textEnv">
          </td>
          <td>
            <input type="text" id="VariableValue1" name="VariableValue[]" class="textEnv">
            <input onclick="addEnv(this.form);" type="button" value="Add Variable">
          </td>
        </tr>
        <?if(isset($templateVariables)){echo $templateVariables;}?>
      </tbody>
    </table>

    <div>
      <br><input type="submit" value="Apply" style="font-weight: bold; font-size: 16px;" onclick="$( '#createContainer' ).submit();$(this).prop('disabled', true);">
    </div>
  </form>
</div>

</body>
</html>

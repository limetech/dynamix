<?PHP
/* Copyright 2015, Lime Technology
 * Copyright 2015, Guilherme Jardim, Eric Schultz, Jon Panozzo.
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
ignore_user_abort(true);
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
$DockerClient = new DockerClient();
$DockerUpdate = new DockerUpdate();
$DockerTemplates = new DockerTemplates();

#   ███████╗██╗   ██╗███╗   ██╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗███████╗
#   ██╔════╝██║   ██║████╗  ██║██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║██╔════╝
#   █████╗  ██║   ██║██╔██╗ ██║██║        ██║   ██║██║   ██║██╔██╗ ██║███████╗
#   ██╔══╝  ██║   ██║██║╚██╗██║██║        ██║   ██║██║   ██║██║╚██╗██║╚════██║
#   ██║     ╚██████╔╝██║ ╚████║╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║███████║
#   ╚═╝      ╚═════╝ ╚═╝  ╚═══╝ ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝

$echo = function($m){echo "<pre>".print_r($m,true)."</pre>";};

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

function ImageExist($image){
  global $DockerClient;
  $all_images = $DockerClient->getDockerImages();
  if ( ! $all_images) { return FALSE; }
  foreach ($all_images as $img) {
    if ( ! is_bool(strpos($img['Tags'][0], $image)) ){
      return True;
      break;
    }
  }
  return False;
}

function trimLine($text){
  return preg_replace("/([\n^])[\s]+/", '$1', $text);
}

$pullecho = function($line) {
  global $alltotals;
  $cnt =  json_decode( $line, TRUE );
  $id = ( isset( $cnt['id'] )) ? $cnt['id'] : "";
  $status = ( isset( $cnt['status'] )) ? $cnt['status'] : "";
  if (strlen(trim($status)) && strlen(trim($id))) {
    if ( isset($cnt['progressDetail']['total']) && $cnt['progressDetail']['total'] > 0 ) {
      $alltotals[$cnt['id']] = $cnt['progressDetail']['total'];
    }
    echo "<script>addToID('${id}','${status}');</script>\n";
    @flush();
  }
  if ($status == "Downloading") {
    $total = $cnt['progressDetail']['total'];
    $current = $cnt['progressDetail']['current'];
    $alltotals[$cnt['id']] = $cnt['progressDetail']['current'];
    if ($total > 0) {
      $percentage = round(($current/$total) * 100);
      echo "<script>progress('${id}',' ". $percentage ."% of " . sizeToHuman($total) . "');</script>\n";
    } else {
      // Docker must not know the total download size (http-chunked or something?)
      //  just show the current download progress without the percentage
      echo "<script>progress('${id}',' " . sizeToHuman($current) . "');</script>\n";
    }
    @flush();
  }
};


function pullImage($image) {
  global $DockerClient, $pullecho, $alltotals;
  $alltotals = array();
  if (! preg_match("/:[\w]*$/i", $image)) $image .= ":latest";
  readfile("/usr/local/emhttp/plugins/dynamix.docker.manager/log.htm");
  echo "<script>
  addLog('<fieldset style=\"margin-top:1px;\" class=\"CMD\"><legend>Pulling image: ${image}</legend><p class=\"logLine\" id=\"logBody\"></p></fieldset>');
  function progress(id, prog){ $('.'+id+'_progress:last').text(prog);}
  function addToID(id, m) {
    if ($('#'+id).length === 0){ addLog('<span id=\"'+id+'\">IMAGE ID ['+id+']: </span>');}
    if ($('#'+id).find('.content:last').text() != m){ $('#'+id).append('<span class=\"content\">'+m+'</span><span class=\"'+id+'_progress\"></span>. ');}
  }</script>";
  @flush();

  $DockerClient->pullImage($image, $pullecho);
  echo "<script>addLog('<br><b>TOTAL DATA PULLED:</b> " . sizeToHuman(array_sum($alltotals)) . "<span class=\"progress\"></span>');</script>\n";
}

function sizeToHuman($size) {
  $units = ['B','KB','MB','GB'];
  $unitsIndex = 0;
  while ($size > 1024 && (($unitsIndex+1) < count($units))) {
    $size /= 1024;
    $unitsIndex++;
  }
  return ceil($size) . " " . $units[$unitsIndex];
}

function xml_encode($string) {
  return htmlspecialchars($string, ENT_XML1, 'UTF-8');
}
function xml_decode($string) {
  return strval(html_entity_decode($string, ENT_XML1, 'UTF-8'));
}

function postToXML($post, $setOwnership = FALSE){
  $dom = new domDocument; 
  $dom->appendChild($dom->createElement( "Container" )); 
  $xml = simplexml_import_dom( $dom ); 
  $xml["version"]          = 2;
  $xml->Name               = xml_encode($post['contName']);
  $xml->Repository         = xml_encode($post['contRepository']);
  $xml->Registry           = xml_encode($post['contRegistry']);
  $xml->Network            = xml_encode($post['contNetwork']);
  $xml->Privileged         = (strtolower($post["contPrivileged"]) == 'on') ? 'true' : 'false'; 
  $xml->Support            = xml_encode($post['contSupport']);
  $xml->Overview           = xml_encode($post['contOverview']);
  $xml->Category           = xml_encode($post['contCategory']);
  $xml->WebUI              = xml_encode($post['contWebUI']);
  $xml->Icon               = xml_encode($post['contIcon']);
  $xml->ExtraParams        = xml_encode($post['contExtraParams']);

  # V1 compatibility
  $xml->Description      = xml_encode($post['contOverview']);
  $xml->Networking->Mode = xml_encode($post['contNetwork']);
  $xml->Networking->addChild("Publish");
  $xml->addChild("Data");
  $xml->addChild("Environment");


  for ($i = 0; $i < count($post["confName"]); $i++) {
    $Type                  = $post['confType'][$i];
    $config                = $xml->addChild('Config');
    $config->{0}           = xml_encode($post['confValue'][$i]);
    $config['Name']        = xml_encode($post['confName'][$i]);
    $config['Target']      = xml_encode($post['confTarget'][$i]);
    $config['Default']     = xml_encode($post['confDefault'][$i]);
    $config['Mode']        = xml_encode($post['confMode'][$i]);
    $config['Description'] = xml_encode($post['confDescription'][$i]);
    $config['Type']        = xml_encode($post['confType'][$i]);
    $config['Display']     = xml_encode($post['confDisplay'][$i]);
    $config['Required']    = xml_encode($post['confRequired'][$i]);
    $config['Mask']        = xml_encode($post['confMask'][$i]);
    # V1 compatibility
    if ($Type == "Port"){
      $port                = $xml->Networking->Publish->addChild("Port");
      $port->HostPort      = $post['confValue'][$i];
      $port->ContainerPort = $post['confTarget'][$i];
      $port->Protocol      = $post['confMode'][$i];
    } else if ($Type == "Path"){
      $path               = $xml->Data->addChild("Volume");
      $path->HostDir      = $post['confValue'][$i];
      $path->ContainerDir = $post['confTarget'][$i];
      $path->Mode         = $post['confMode'][$i];
    } else if ($Type == "Variable"){
      $variable        = $xml->Environment->addChild("Variable");
      $variable->Value = $post['confValue'][$i];
      $variable->Name  = $post['confTarget'][$i];
      $variable->Mode  = $post['confMode'][$i];
    }
  }
  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = false;
  $dom->formatOutput = true;
  $dom->loadXML($xml->asXML());
  return $dom->saveXML();
}

function xmlToVar($xml) {
  global $var;
  $out           = array();
  $xml           = (is_file($xml)) ? simplexml_load_file($xml) : simplexml_load_string($xml);

  $out['Name']        = xml_decode($xml->Name);
  $out['Repository']  = xml_decode($xml->Repository);
  $out['Registry']    = xml_decode($xml->Registry);
  $out['Network']     = (isset($xml->Network)) ? xml_decode($xml->Network) : xml_decode($xml->Network['Default']);
  $out['Privileged']  = xml_decode($xml->Privileged);
  $out['Support']     = xml_decode($xml->Support);
  $out['Overview']    = stripslashes(xml_decode($xml->Overview));
  $out['Category']    = xml_decode($xml->Category);
  $out['WebUI']       = xml_decode($xml->WebUI);
  $out['Icon']        = xml_decode($xml->Icon);
  $out['ExtraParams'] = xml_decode($xml->ExtraParams);

  $out['Config'] = array();
  if (isset($xml->Config)) {
    foreach ($xml->Config as $config) {
      $c = array();
      $c['Value'] = strlen(xml_decode($config)) ? xml_decode($config) : xml_decode($config['Default']) ;
      foreach ($config->attributes() as $key => $value) $c[$key] = xml_decode(xml_decode($value));
      $out['Config'][] = $c;
    }
  }

  # V1 compatibility
  if ($xml["version"] != "2"){
    if (isset($xml->Networking->Mode)){
      $out['Network'] = xml_decode($xml->Networking->Mode);
    }
    if (isset($xml->Description)) {
      $out['Overview'] = stripslashes(xml_decode($xml->Description));
    }

    if (isset($xml->Networking->Publish->Port)) {
      $portNum = 0;
      foreach ($xml->Networking->Publish->Port as $port) {
        $portNum += 1;
        $out['Config'][] = array('Name'       => "Port ${portNum}",
                                 'Target'      => xml_decode($port->ContainerPort),
                                 'Default'     => xml_decode($port->HostPort),
                                 'Value'       => xml_decode($port->HostPort),
                                 'Mode'        => xml_decode($port->Protocol),
                                 'Description' => '',
                                 'Type'        => 'Port',
                                 'Display'     => 'always',
                                 'Required'    => 'true',
                                 'Mask'        => 'no',
                                 );
      }   
    }

    if (isset($xml->Data->Volume)) {
      $volNum = 0;
      foreach ($xml->Data->Volume as $vol) {
        $volNum += 1;
        $out['Config'][] = array('Name'       => "Path ${volNum}",
                                 'Target'      => xml_decode($vol->ContainerDir),
                                 'Default'     => xml_decode($vol->HostDir),
                                 'Value'       => xml_decode($vol->HostDir),
                                 'Mode'        => xml_decode($vol->Mode),
                                 'Description' => '',
                                 'Type'        => 'Path',
                                 'Display'     => 'always',
                                 'Required'    => 'true',
                                 'Mask'        => 'no',
                                 );
      } 
    }

    if (isset($xml->Environment->Variable)) {
      $varNum = 0;
      foreach ($xml->Environment->Variable as $var) {
        $varNum += 1;
        $out['Config'][] = array('Name'       => "Variable ${varNum}",
                                 'Target'      => xml_decode($var->Name),
                                 'Default'     => xml_decode($var->Value),
                                 'Value'       => xml_decode($var->Value),
                                 'Mode'        => '',
                                 'Description' => '',
                                 'Type'        => 'Variable',
                                 'Display'     => 'always',
                                 'Required'    => 'false',
                                 'Mask'        => 'no',
                                 );
      }
    }
  }

  return $out;
}

function xmlToCommand($xml) {
  global $var;
  $xml           = xmlToVar($xml);
  $cmdName       = (strlen($xml['Name'])) ? '--name="' . $xml['Name'] . '"' : "";
  $cmdPrivileged = (strtolower($xml['Privileged']) == 'true') ?  '--privileged="true"' : "";
  $cmdNetwork    = '--net="'.strtolower($xml['Network']).'"';
  $Volumes       = array('');
  $Ports         = array('');
  $Variables     = array('');
  $Devices       = array('');
  # Bind Time
  $Variables[]   = 'TZ="' . $var['timeZone'] . '"';
  # Add HOST_OS variable
  $Variables[]   = 'HOST_OS="unRAID"';

  foreach ($xml['Config'] as $key => $config) {
    $confType        = strtolower(strval($config['Type']));
    $hostConfig      = strlen($config['Value']) ? $config['Value'] : $config['Default'];
    $containerConfig = strval($config['Target']);
    $Mode            = strval($config['Mode']);
    if (! strlen($containerConfig)) continue;
    if ($confType == "path") {
      $Volumes[] = sprintf( '"%s":"%s":%s', $hostConfig, $containerConfig, $Mode);
    } elseif ($confType == 'port') {
      $Ports[] = sprintf("%s:%s/%s", $hostConfig, $containerConfig, $Mode);
    } elseif ($confType == "variable") {
      $Variables[] = sprintf('%s="%s"', $containerConfig, $hostConfig);
    } elseif ($confType == "device") {
      $Devices[] = '"'.$containerConfig.'"';
    }
  }
  $cmd = sprintf('/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker run -d %s %s %s %s %s %s %s %s %s', 
                 $cmdName,
                 $cmdNetwork,
                 $cmdPrivileged, 
                 implode(' -e ', $Variables),
                 implode(' -p ', $Ports), 
                 implode(' -v ', $Volumes), 
                 implode(' --device=', $Devices), 
                 $xml['ExtraParams'], 
                 $xml['Repository']);

  $cmd = preg_replace('/\s+/', ' ', $cmd);
  return array($cmd, $xml['Name'], $xml['Repository']);
}

function getXmlVal($xml, $element, $attr=null, $pos=0) {
  $xml = (is_file($xml)) ? simplexml_load_file($xml) : simplexml_load_string($xml);
  $element = $xml->xpath("//$element")[$pos];
  return isset($element) ? (isset($element[$attr]) ? strval($element[$attr]) : strval($element)) : "";
}

function setXmlVal(&$xml, $value, $el, $attr=null, $pos=0) {
  global $echo;
  $xml = (is_file($xml)) ? simplexml_load_file($xml) : simplexml_load_string($xml);
  $element = $xml->xpath("//$el")[$pos];
  if (! isset($element)) $element = $xml->addChild($el);
  if ($attr) {
    $element[$attr] = $value;
  } else {
    $element->{0} = $value;
  }
  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = false;
  $dom->formatOutput = true;
  $dom->loadXML($xml->asXML());
  $xml = $dom->saveXML();
}


#    ██████╗ ██████╗ ██████╗ ███████╗
#   ██╔════╝██╔═══██╗██╔══██╗██╔════╝
#   ██║     ██║   ██║██║  ██║█████╗  
#   ██║     ██║   ██║██║  ██║██╔══╝  
#   ╚██████╗╚██████╔╝██████╔╝███████╗
#    ╚═════╝ ╚═════╝ ╚═════╝ ╚══════╝

##
##   CREATE CONTAINER
##

if (isset($_POST['contName'])) {

  $postXML = postToXML($_POST, TRUE);

  // Get the command line
  list($cmd, $Name, $Repository) = xmlToCommand($postXML);
  
  // Run dry
  if ($_POST['dryRun'] == "true") {
    echo "<h2>XML</h2>";
    echo "<pre>".htmlentities($postXML)."</pre>";
    echo "<h2>COMMAND:</h2>";
    echo "<pre>".htmlentities($cmd)."</pre>";
    echo '<center><input type="button" value="Done" onclick="done()"></center><br>';
    goto END;
  }
 
  // Will only pull image if it's absent
  if (! ImageExist($Repository)) {
    // Pull image
    pullImage($Repository);    
  }

  // Saving the generated configuration file.
  $userTmplDir = $dockerManPaths['templates-user'];
  if(is_dir($userTmplDir) === FALSE){
    mkdir($userTmplDir, 0777, true);
  }
  if(strlen($Name)) {
    $filename = sprintf('%s/my-%s.xml', $userTmplDir, $Name);
    file_put_contents($filename, $postXML);
  }

  // Remove existing container
  if (ContainerExist($Name)){
    $_GET['cmd'] = "/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker rm -f $Name";
    include($dockerManPaths['plugin'] . "/include/Exec.php");
  }

  // Remove old container if renamed
  $existing = isset($_POST['existingContainer']) ? $_POST['existingContainer'] : FALSE;
  if ($existing && ContainerExist($existing)){
    $_GET['cmd'] = "/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker rm -f $existing";
    include($dockerManPaths['plugin'] . "/include/Exec.php");
  }

  // Injecting the command in $_GET variable and executing.
  $_GET['cmd'] = $cmd;
  include($dockerManPaths['plugin'] . "/include/Exec.php");

  // Force information reload
  $DockerTemplates->removeInfo($Name, $Repository);
  $DockerUpdate->reloadUpdateStatus($Repository);

  echo '<center><input type="button" value="Done" onclick="done()"></center><br>';
  goto END;
}

##
##   UPDATE CONTAINER
##
if ($_GET['updateContainer']){
  foreach ($_GET['ct'] as $value) {
    $Name = urldecode($value);
    $tmpl = $DockerTemplates->getUserTemplate($Name);

    if (! $tmpl){
      echo 'Configuration not found. Was this container created using this plugin?';
      continue;
    }

    $xml = file_get_contents($tmpl);
    list($cmd, $Name, $Repository) = xmlToCommand($tmpl);
    $Registry = getXmlVal($xml, "Registry");

    readfile("/usr/local/emhttp/plugins/dynamix.docker.manager/log.htm");
    echo "<script>addLog('<p>Preparing to update: " . $Repository . "</p>');</script>";
    @flush();

    $oldContainerID = $DockerClient->getImageID($Repository);
    
    // Pull image
    flush();
    pullImage($Repository);

    $_GET['cmd'] = "/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker rm -f $Name";
    include($dockerManPaths['plugin'] . "/include/Exec.php");

    $_GET['cmd'] = $cmd;
    include($dockerManPaths['plugin'] . "/include/Exec.php");

    $newContainerID = $DockerClient->getImageID($Repository);
    if ( $oldContainerID and $oldContainerID != $newContainerID){
      $_GET['cmd'] = sprintf("/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker rmi %s", $oldContainerID);
      include($dockerManPaths['plugin'] . "/include/Exec.php");
    }

    // Force information reload
    $DockerTemplates->removeInfo($Name, $Repository);
    $DockerUpdate->reloadUpdateStatus($Repository);
  }

  echo '<center><input type="button" value="Done" onclick="window.parent.jQuery(\'#iframe-popup\').dialog(\'close\');"></center><br>';
  goto END;
}

##
##   REMOVE TEMPLATE
##

if($_GET['rmTemplate']){
  unlink($_GET['rmTemplate']);
}

##
##   LOAD TEMPLATE
##

if ($_GET['xmlTemplate']) {
  list($xmlType, $xmlTemplate) = split(':', urldecode($_GET['xmlTemplate']));
  if(is_file($xmlTemplate)){
    $xml = xmlToVar($xmlTemplate);
    $templateName = $xml["Name"];
    $xml['Description'] = str_replace(array('[', ']'), array('<', '>'), $xml['Overview']);
    echo "<script>var Settings=".json_encode($xml).";</script>";
  }
}

$showAdditionalInfo = true;
?>

<link type="text/css" rel="stylesheet" href="/webGui/styles/font-awesome.css">

<?if (is_file("webGui/scripts/jquery.switchButton.js")): # Pre 6.1?>
<script type="text/javascript" src="/webGui/scripts/jquery.switchButton.js"></script>
<script type="text/javascript" src="/webGui/scripts/jqueryFileTree.js"></script>
<link type="text/css" rel="stylesheet" href="/webGui/styles/jquery-ui.min.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/jquery.switchButton.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/jqueryFileTree.css" >

<?else: # Post 6.1?>
<script type="text/javascript" src="/webGui/javascript/jquery.switchbutton.js"></script>
<script type="text/javascript" src="/webGui/javascript/jquery.filetree.js"></script>
<link type="text/css" rel="stylesheet" href="/webGui/styles/jquery.ui.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/jquery.switchbutton.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/jquery.filetree.css" >
<?endif;?>

<style>
  body{-webkit-overflow-scrolling:touch;}
  .fileTree{width:240px;height:150px;overflow:scroll;position:absolute;z-index:100;display:none;margin-bottom: 100px;}
  #TemplateSelect{width:255px;}
  input.textTemplate,textarea.textTemplate{width:555px;}
  option.list{padding:0 0 0 7px;font-size:11px;}
  optgroup.bold{font-weight:bold;font-size:12px;margin-top:5px;}
  optgroup.title{background-color:#625D5D;color:#FFFFFF;text-align:center;margin-top:10px;}
  .textPath{width:270px;}

  table.Preferences{width:100%;}
  table.Preferences tr>td{font-weight: bold;padding-right: 10px;}
  table.Preferences tr>td+td{font-weight: normal;}
  .show{display:block;}
  table td{font-size:14px;vertical-align:bottom;text-align:left;}
  .inline_help{font-size:12px;font-weight: normal;}
  .desc{padding:6px;line-height:15px;width:inherit;}
  .toggleMode{cursor:pointer;color:#a3a3a3;letter-spacing:0;padding:0;padding-right:10px;font-family:arimo;font-size:12px;line-height:1.3em;font-weight:bold;margin:0;}
  .toggleMode:hover,.toggleMode:focus,.toggleMode:active,.toggleMode .active{color:#625D5D;}
  .advanced{display: none;}
  .required:after {content: " * ";color: #E80000}

  .switch-wrapper {
    display: inline-block;
    position: relative;
    top: 3px;
    vertical-align: middle;
  }
  .spacer{padding-right: 20px}

  .label-warning, .label-success, .label-important, orange, red, green {
    padding: 1px 4px 2px;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px;
    font-size: 10.998px;
    font-weight: bold;
    line-height: 14px;
    color: #ffffff;
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
    white-space: nowrap;
    vertical-align: middle;
  }
  .label-warning, orange {
    background-color: #f89406;
  }
  .label-success, green {
    background-color: #468847;
  }
  .label-important, red {
    background-color: #b94a48;
  }
</style>
<script type="text/javascript">
  var this_tab = $('input[name$="tabs"]').length;
  $(function() {
    var content= "<div class='switch-wrapper'><input type='checkbox' class='advanced-switch'></div>";
    <?if (!$tabbed):?>
    $("#docker_tabbed").html(content);
    <?else:?>
    var last = $('input[name$="tabs"]').length;
    var elementId = "normalAdvanced";
    $('.tabs').append("<span id='"+elementId+"' class='status vhshift' style='display: none;'>"+content+"&nbsp;</span>");
    if ($('#tab'+this_tab).is(':checked')) {
      $('#'+elementId).show();
    }
    $('#tab'+this_tab).bind({click:function(){$('#'+elementId).show();}});
    for (var x=1; x<=last; x++) if(x != this_tab) $('#tab'+x).bind({click:function(){$('#'+elementId).hide();}});
      <?endif;?>
    // $('.advanced-switch').switchButton({ labels_placement: "left", on_label: 'Advanced', off_label: 'Normal', checked: $.cookie('docker-advanced-view') != 'false'});
    $('.advanced-switch').switchButton({ labels_placement: "left", on_label: 'Advanced', off_label: 'Normal'});
    $('.advanced-switch').change(function () {
      status = $(this).is(':checked');
      $('.advanced').toggle(status);
      // $.cookie('docker-advanced-view', status ? 'true' : 'false', { expires: 3650 });
    });

    $("#app_config_tab").html("<div class='switch-wrapper'><input type='checkbox' class='hidden-switch'></div>");
    // $('.hidden-switch').switchButton({ labels_placement: "left", on_label: 'Show Hidden', off_label: 'Show Hidden', checked: $.cookie('docker-hidden-view') != 'false'});
    $('.hidden-switch').switchButton({ labels_placement: "left", on_label: 'Show Hidden', off_label: 'Show Hidden'});
    $('.hidden-switch').change(function () {
      status = $(this).is(':checked');
      $('.hidden').toggle(status);
      // $.cookie('docker-hidden-view', status ? 'true' : 'false', { expires: 3650 });
    });
  });
</script>
<script type="text/javascript">
  var confNum = 0;

  if ( !Array.prototype.forEach ) {
    Array.prototype.forEach = function(fn, scope) {
      for(var i = 0, len = this.length; i < len; ++i) {
        fn.call(scope, this[i], i, this);
      }
    };
  }

  if (!String.prototype.format) {
    String.prototype.format = function() {
      var args = arguments;
      return this.replace(/{(\d+)}/g, function(match, number) {
        return typeof args[number] != 'undefined' ? args[number] : match;
      });
    };
  }

  // Create config nodes using templateDisplayConfig
  function makeConfig(opts) {
    confNum += 1;
    newConfig = $("#templateDisplayConfig").html();
    newConfig = newConfig.format(opts.Name, 
                                 opts.Target, 
                                 opts.Default,
                                 opts.Mode,
                                 opts.Description,
                                 opts.Type,
                                 opts.Display, 
                                 opts.Required,
                                 opts.Mask,
                                 opts.Value,
                                 opts.Buttons,
                                 (opts.Required == "true") ? "required" : ""
                                 );
    newConfig = "<div id='ConfigNum"+opts.Number+"' style='margin-top: 30px;' class='"+opts.Display+"'>"+newConfig+"</div>";
    newConfig = $($.parseHTML(newConfig));
    value     = newConfig.find("input[name='confValue[]']");
    if (opts.Type == "Path") {
      value.attr("onclick", "openFileBrowser(this,$(this).val(),'',true,false);");
    } else if (opts.Type == "Device") {
      value.attr("onclick", "openFileBrowser(this,$(this).val(),'',false,true);")
    } else if (opts.Type == "Variable" && opts.Default.split("|").length > 1) {
      var valueOpts = opts.Default.split("|");
      var newValue = "<select name='confValue[]' class='textPath' default='"+valueOpts[0]+"'>";
      for (var i = 0; i < valueOpts.length; i++) {
        newValue += "<option value='"+valueOpts[i]+"' "+(opts.Value == valueOpts[i] ? "selected" : "")+">"+valueOpts[i]+"</option>";
      } 
      newValue += "</select>";
      value.replaceWith(newValue);
    } else if (opts.Type == "Port") {
      value.addClass("numbersOnly");
    }
    if (opts.Mask == "true") {
      value.prop("type", "password");
    }
    return newConfig.prop('outerHTML');
  }

  function getVal(el, name) {
    var el = $(el).find("*[name="+name+"]");
    if (el.length) {
      return ( $(el).attr('type') == 'checkbox' ) ? ($(el).is(':checked') ? "on" : "off") : $(el).val();
    } else {
      return "";
    }
  }

  function addConfigPopup(){
    var title = 'Add Configuration';
    var popup = $( "#dialogAddConfig" );

    // Load popup the popup with the template info
    popup.html($("#templatePopupConfig").html());

    // Add switchButton to checkboxes
    popup.find(".switch").switchButton({labels_placement:"right",on_label:'YES',off_label:'NO'});
    popup.find(".switch-button-background").css("margin-top", "6px");

    // Load Mode field if needed
    toggleMode(popup.find("*[name=Type]:first"));

    // Start Dialog section
    popup.dialog({
      title: title,
      resizable: false,
      width: 600,
      modal: true,
      show : {effect: 'fade' , duration: 250},
      hide : {effect: 'fade' , duration: 250},
      buttons: {
        "Add": function() {
          $( this ).dialog( "close" );
          confNum += 1;
          var Opts = Object; 
          var Element = this;
          ["Name","Target","Default","Mode","Description","Type","Display","Required","Mask","Value"].forEach(function(e){
            Opts[e] = getVal(Element, e);
          });
          Opts.Description = (Opts.Description.length) ? Opts.Description : "Container "+Opts.Type+": "+Opts.Target;
          if (Opts.Required == "true") {
            Opts.Buttons     = "<span class='advanced'><button type='button' onclick='editConfigPopup("+confNum+")'> Edit</button> ";
            Opts.Buttons    += "<button type='button' onclick='removeConfig("+confNum+");'> Remove</button></span>";
          } else {
            Opts.Buttons     = "<button type='button' onclick='editConfigPopup("+confNum+")'> Edit</button> ";
            Opts.Buttons    += "<button type='button' onclick='removeConfig("+confNum+");'> Remove</button>";
          }
          Opts.Number      = confNum;
          newConf = makeConfig(Opts);
          $("#configLocation").append(newConf);
          reloadTriggers();
        },
        Cancel: function() {
          $( this ).dialog( "close" );
        }
      }
    });
    $(".ui-dialog .ui-dialog-titlebar").addClass('menu');
    $(".ui-dialog .ui-dialog-title").css('text-align','center').css( 'width', "100%");
    $(".ui-dialog .ui-dialog-content").css('padding-top','15px').css('vertical-align','bottom');
    $(".ui-button-text").css('padding','0px 5px');
  }

  function editConfigPopup(num){
    var title = 'Edit Configuration';
    var popup = $("#dialogAddConfig");

    // Load popup the popup with the template info
    popup.html($("#templatePopupConfig").html());

    // Load existing config info
    var config = $( "#ConfigNum" + num );
    config.find("input").each(function(){
      var name = $(this).attr( "name" ).replace("conf", "").replace("[]", "");
      popup.find("*[name='"+name+"']").val( $(this).val() );
    });

    // Hide passwords if needed
    if (popup.find("*[name='Mask']").val() == "true") {
      popup.find("*[name='Value']").prop("type", "password");
    }

    // Load Mode field if needed
    var mode = config.find("input[name='confMode[]']").val();
    toggleMode(popup.find("*[name=Type]:first"));
    popup.find("*[name=Mode]:first").val(mode);

    // Add switchButton to checkboxes
    popup.find(".switch").switchButton({labels_placement:"right",on_label:'YES',off_label:'NO'});

    // Start Dialog section
    popup.find(".switch-button-background").css("margin-top", "6px");
    popup.dialog({
      title: title,
      resizable: false,
      width: 600,
      modal: true,
      show : {effect: 'fade' , duration: 250},
      hide : {effect: 'fade' , duration: 250},
      buttons: {
        "Save": function() {
          $( this ).dialog( "close" );
          var Opts = Object; 
          var Element = this;
          ["Name","Target","Default","Mode","Description","Type","Display","Required","Mask","Value"].forEach(function(e){
            Opts[e] = getVal(Element, e);
          });
          Opts.Description = (Opts.Description.length) ? Opts.Description : "Container "+Opts.Type+": "+Opts.Target;
          if (Opts.Required == "true") {
            Opts.Buttons     = "<span class='advanced'><button type='button' onclick='editConfigPopup("+num+")'> Edit</button> ";
            Opts.Buttons    += "<button type='button' onclick='removeConfig("+num+");'> Remove</button></span>";
          } else {
            Opts.Buttons     = "<button type='button' onclick='editConfigPopup("+num+")'> Edit</button> ";
            Opts.Buttons    += "<button type='button' onclick='removeConfig("+num+");'> Remove</button>";
          }
          Opts.Number      = confNum;
          newConf = makeConfig(Opts);
          config.removeClass("always advanced hidden").addClass(Opts.Display);
          $("#ConfigNum" + num).html(newConf);
          reloadTriggers();
        },
        Cancel: function() {
          $( this ).dialog( "close" );
        }
      }
    });
    $(".ui-dialog .ui-dialog-titlebar").addClass('menu');
    $(".ui-dialog .ui-dialog-title").css('text-align','center').css( 'width', "100%");
    $(".ui-dialog .ui-dialog-content").css('padding-top','15px').css('vertical-align','bottom');
    $(".ui-button-text").css('padding','0px 5px');
    $('.desc_readmore').readmore({maxHeight:10});
  }

  function removeConfig(num) {
    $('#ConfigNum' + num).fadeOut("fast", function() { $(this).remove(); });
  }

  function toggleMode(el) {
    var mode       = $(el).parent().siblings('#Mode');
    var valueDiv   = $(el).parent().siblings('#Value');
    var defaultDiv = $(el).parent().siblings('#Default');
    var targetDiv  = $(el).parent().siblings('#Target');

    var value      = valueDiv.find('input[name=Value]');
    var target     = targetDiv.find('input[name=Target]');

    value.unbind();
    target.unbind();

    valueDiv.css('display', '');
    defaultDiv.css('display', '');
    targetDiv.css('display', '');
    mode.html('');

    var index = $(el)[0].selectedIndex;
    if(index == 0){
      // Path
      mode.html("<dt>Mode</dt><dd><select name='Mode'><option value='rw'>Read/Write</option><option value='ro'>Read Only</option></select></dd>");
      value.bind("click",function(){openFileBrowser(this,$(this).val(),'sh',true,false);});
    } else if(index == 1){
      // Port
      mode.html("<dt>Mode</dt><dd><select name='Mode'><option value='tcp'>TCP</option><option value='udp'>UDP</option></select></dd>");
      value.addClass("numbersOnly");
      target.addClass("numbersOnly");
    } else if(index == 3){
      // Device
      targetDiv.css('display', 'none');
      defaultDiv.css('display', 'none');
      value.bind("click",function(){openFileBrowser(this,$(this).val(),'',true,true);});
    }
    reloadTriggers();
  }

  function loadTemplate(el) {
    var template = $(el).val();
    if (template.length) {
      $('#formTemplate').find( "input[name='xmlTemplate']" ).val(template);
      $('#formTemplate').submit();
    }
  }

  function rmTemplate(tmpl) {
    var name = tmpl.split(/[\/]+/).pop();
    swal({title:"Are you sure?",text:"Remove template: "+name,type:"warning",showCancelButton:true},function(){$("#rmTemplate").val(tmpl);$("#formTemplate").submit();});
  }

  function openFileBrowser(el, root, filter, on_folders, on_files, close_on_select) {
    if (on_folders === undefined) on_folders = true;
    if (on_files   === undefined) on_files = true;
    if (! filter && ! on_files)   filter = 'HIDE_FILES_FILTER';
    if (! root.trim() ) root = "/mnt/user/";
    p = $(el);
    // Skip is fileTree is already open
    if ( p.next().hasClass('fileTree') ){return null;}
    // create a random id
    var r = Math.floor((Math.random()*1000)+1);
    // Add a new span and load fileTree
    p.after("<span id='fileTree"+r+"' class='textarea fileTree'></span>");
    var ft = $('#fileTree'+r);
    ft.fileTree({
      root: root,
      filter: filter,
      allowBrowsing : true
    },
    function(file){if(on_files){p.val(file);if(close_on_select){ft.slideUp('fast',function (){ft.remove();});}}},
    function(folder){if(on_folders){p.val(folder);if(close_on_select){$(ft).slideUp('fast',function (){$(ft).remove();});}}}
    );
    // Format fileTree according to parent position, height and width
    ft.css({'left':p.position().left,'top':( p.position().top + p.outerHeight() ),'width':(p.width()) });
    // close if click elsewhere
    $(document).mouseup(function(e){if(!ft.is(e.target) && ft.has(e.target).length === 0){ft.slideUp('fast',function (){$(ft).remove();});}});
    // close if parent changed
    p.bind("keydown",function(){ft.slideUp('fast',function (){$(ft).remove();});});
    // Open fileTree
    ft.slideDown('fast');
  }

  function resetField(el) {
    var target = $(el).prev();
    reset = target.attr("default");
    if (reset.length) {
      target.val(reset);
    }
  }

</script>
<div style='display: inline; float: right; margin: -47px -5px;' id='docker_tabbed'></div>
<div id="dialogAddConfig" style="display: none"></div>
<form method="GET" id="formTemplate">
  <input type="hidden" id="xmlTemplate" name="xmlTemplate" value="" />
  <input type="hidden" id="rmTemplate" name="rmTemplate" value="" />
</form>

<form method="POST" autocomplete="off">
  <div id="canvas" style="z-index:1;">
    <table class="Preferences">
      <? if($xmlType == "edit"):
      if (ContainerExist($templateName)): echo "<input type='hidden' name='existingContainer' value='${templateName}'>\n"; endif;
      else:?>
      <tr>
        <td>Template:</td>
        <td>
          <select id="TemplateSelect" size="1" onchange="loadTemplate(this);">
            <option value="">Select a template</option>
            <?
            $rmadd = '';
            $all_templates = array();
            $all_templates['user'] = $DockerTemplates->getTemplates("user");
            $all_templates['default'] = $DockerTemplates->getTemplates("default");
            foreach ($all_templates as $key => $templates) {
              if ($key == "default") $title = "Default templates";
              if ($key == "user") $title = "User defined templates";
              printf("\t\t\t\t\t<optgroup class=\"title bold\" label=\"[ %s ]\"></optgroup>\n", htmlspecialchars($title));
              $prefix = '';
              foreach ($templates as $value){
                if ($value["prefix"] != $prefix) {
                  if ($prefix != '') {
                    printf("\t\t\t\t\t</optgroup>\n");
                  }
                  $prefix = $value["prefix"];
                  printf("\t\t\t\t\t<optgroup class=\"bold\" label=\"[ %s ]\">\n", htmlspecialchars($prefix));
                }
                //$value['name'] = str_replace("my-", '', $value['name']);
                $selected = (isset($xmlTemplate) && $value['path'] == $xmlTemplate) ? ' selected ' : '';
                if ($selected && ($key == "default")) $showAdditionalInfo = false;
                if (strlen($selected) && $key == 'user' ){ $rmadd = $value['path']; }
                printf("\t\t\t\t\t\t<option class=\"list\" value=\"%s:%s\" {$selected} >%s</option>\n", htmlspecialchars($key), htmlspecialchars($value['path']), htmlspecialchars($value['name']));
              }
              printf("\t\t\t\t\t</optgroup>\n");
            }
            ?>
          </select>
          <? if (!empty($rmadd)) {
            echo "<a onclick=\"rmTemplate('" . addslashes($rmadd) . "');\" style=\"cursor:pointer;\"><img src=\"/plugins/dynamix.docker.manager/images/remove.png\" title=\"" . htmlspecialchars($rmadd) . "\" width=\"30px\"></a>";
          }?>
        </td>
      </tr>
      <tr class="inline_help" style="display: none">
        <td colspan="2">
          <blockquote class="inline_help">
            <p>Templates are a quicker way to setting up Docker Containers on your unRAID server.  There are two types of templates:</p>

            <p>
              <b>Default templates</b><br>
              When valid repositories are added to your Docker Repositories page, they will appear in a section on this drop down for you to choose (master categorized by author, then by application template).  After selecting a default template, the page will populate with new information about the application in the Description field, and will typically provide instructions for how to setup the container.  Select a default template when it is the first time you are configuring this application.
            </p>

            <p>
              <b>User-defined templates</b><br>
              Once you've added an application to your system through a Default template, the settings you specified are saved to your USB flash device to make it easy to rebuild your applications in the event an upgrade were to fail or if another issue occurred.  To rebuild, simply select the previously loaded application from the User-defined list and all the settings for the container will appear populated from your previous setup.  Clicking create will redownload the necessary files for the application and should restore you to a working state.  To delete a User-defined template, select it from the list above and click the red X to the right of it.
            </p>
          </blockquote>
        </td>
      </tr>
      <?endif;?>
      <tr>
        <td style="width: 150px; vertical-align: top;">Name:</td>
        <td><input type="text" name="contName" class="textPath" required></td>
      </tr>
      <tr class="inline_help" style="display: none">
        <td colspan="2">
          <blockquote class="inline_help">
            <p>Give the container a name or leave it as default.</p>
          </blockquote>
        </td>
      </tr>
      <tr id="Overview">
        <td style="width: 150px; vertical-align: top;">Overview:</td>
        <td><div style="color: #3B5998; width:50%;" name="contDescription"></div></td>
      </tr>
      <tr <?if(!$showAdditionalInfo) echo "class='advanced'";?>>
        <td>Repository:</td>
        <td><input type="text" name="contRepository" class="textPath" required></td>
      </tr>
      <tr <?if(!$showAdditionalInfo) echo "class='advanced'";?>>
        <td colspan="2" class="inline_help" style="display:none">
          <blockquote class="inline_help">
            <p>The repository for the application on the Docker Registry.  Format of authorname/appname.  Optionally you can add a : after appname and request a specific version for the container image.</p>
          </blockquote>
        </td>
      </tr>
      <tr>
        <td>Network Type:</td>
        <td>
          <select name="contNetwork" class="textPath">
            <option value="bridge">Bridge</option>
            <option value="host">Host</option>
            <option value="none">None</option>
          </select>
        </td>
      </tr>
      <tr class="inline_help" style="display:none">
        <td colspan="2">
          <blockquote class="inline_help">
            <p>If the Bridge type is selected, the application’s network access will be restricted to only communicating on the ports specified in the port mappings section.  If the Host type is selected, the application will be given access to communicate using any port on the host that isn’t already mapped to another in-use application/service.  Generally speaking, it is recommended to leave this setting to its default value as specified per application template.</p>
            <p>IMPORTANT NOTE:  If adjusting port mappings, do not modify the settings for the Container port as only the Host port can be adjusted.</p>
          </blockquote>
        </td>
      </tr>
      <tr <?if(!$showAdditionalInfo) echo "class='advanced'";?>>
        <td>Privileged:</td>
        <td><input type="checkbox" name="contPrivileged" class="switch-on-off">
        </td>
      </tr>
      <tr <?if(!$showAdditionalInfo) echo "class='advanced'";?>>
        <td colspan="2" class="inline_help" style="display:none">
          <blockquote class="inline_help">
            <p>For containers that require the use of host-device access directly or need full exposure to host capabilities, this option will need to be selected.  For more information, see this link:  <a href="https://docs.docker.com/reference/run/#runtime-privilege-linux-capabilities-and-lxc-configuration" target="_blank">https://docs.docker.com/reference/run/#runtime-privilege-linux-capabilities-and-lxc-configuration</a></p>
          </blockquote>
        </td>
      </tr>
    </table>
    <div id="title"><span class="left"><img src="/plugins/dynamix.docker.manager/icons/addcontainer.png" class="icon">App Configuration:</span></div>
    <div style='display: inline; float: right; margin: -47px -5px;' id='app_config_tab'></div>
    <div id="configLocation"></div>
    <table>
      <tr>
        <td style="vertical-align: top; width: 150px; white-space:nowrap; "> &nbsp;</td>
        <td><button type="button" onclick="addConfigPopup();" style="margin-top: 25px">Add Config</button></td>
      </tr>
    </table>
    
    <div class="advanced">
      <div id="title">
        <span class="left"><img src="/plugins/dynamix.docker.manager/icons/vcard.png" class="icon">Additional Fields</span>
      </div>
      <table class="Preferences">
        <tr>
          <td style="width: 150px; vertical-align: top;">Overview:</td>
          <td><textarea name="contOverview" rows="10" cols="71" class="textTemplate"></textarea></td>
        </tr>
        <tr class="inline_help" style="display: none">
          <td colspan="2">
            <blockquote class="inline_help">
              <p>A description for the application container.  Supports basic HTML mark-up.</p>
            </blockquote>
          </td>
        </tr>
        <tr>
          <td>Categories:</td>
          <td><input type="text" name="contCategory" class="textPath"></td>
        </tr>
        <tr>
          <td>Support Thread:</td>
          <td><input type="text" name="contSupport" class="textPath"></td>
        </tr>
        <tr class="inline_help" style="display: none">
          <td colspan="2">
            <blockquote class="inline_help">
              <p>Lnk to a support thread on Lime-Technology's forum.</p>
            </blockquote>
          </td>
        </tr>
        <tr>
          <td>Docker Hub URL:</td>
          <td><input type="text" name="contRegistry" class="textPath"></td>
        </tr>
        <tr>
          <td colspan="2" class="inline_help" style="display:none">
            <blockquote class="inline_help">
              <p>The path to the container's repository location on the Docker Hub.</p>
            </blockquote>
          </td>
        </tr>
        <tr>
          <td>Icon URL:</td>
          <td><input type="text" name="contIcon" class="textPath"></td>
        </tr>
        <tr class="inline_help" style="display: none">
          <td colspan="2">
            <blockquote class="inline_help">
              <p>Link to the icon image for your application (only displayed on dashboard if Show Dashboard apps under Display Settings is set to Icons).</p>
            </blockquote>
          </td>
        </tr>
        <tr>
          <td>WebUI:</td>
          <td><input type="text" name="contWebUI" class="textPath"></td>
        </tr>
        <tr class="inline_help" style="display: none">
          <td colspan="2">
            <blockquote class="inline_help">
              <p>When you click on an application icon from the Docker Containers page, the WebUI option will link to the path in this field.  Use [IP} to identify the IP of your host and [PORT:####] replacing the #'s for your port.</p>
            </blockquote>
          </td>
        </tr>
        <tr>
          <td>Extra Parameters:</td>
          <td><input type="text" name="contExtraParams" class="textPath"></td>
        </tr>
        <tr>
          <td colspan="2" class="inline_help" style="display:none">
            <blockquote class="inline_help">
              <p>If you wish to append additional commands to your Docker container at run-time, you can specify them here.  For example, if you wish to pin an application to live on a specific CPU core, you can enter "--cpuset=0" in this field.  Change 0 to the core # on your system (starting with 0).  You can pin multiple cores by separation with a comma or a range of cores by separation with a dash.  For all possible Docker run-time commands, see here: <a href="https://docs.docker.com/reference/run/" target="_blank">https://docs.docker.com/reference/run/</a></p>
            </blockquote>
          </td>
        </tr>
      </table>
    </div>
    <input type="submit" value="<?= ($xmlType != 'edit') ? 'Create' : 'Save' ?>">
    <button class="advanced" type="submit" name="dryRun" value="true" onclick="$('*[required]').prop( 'required', null );">Dry Run</button>
    <input type="button" value="Cancel" onclick="done()">
    <br><br><br>
  </div>
</form>

<?
#        ██╗███████╗    ████████╗███████╗███╗   ███╗██████╗ ██╗      █████╗ ████████╗███████╗███████╗
#        ██║██╔════╝    ╚══██╔══╝██╔════╝████╗ ████║██╔══██╗██║     ██╔══██╗╚══██╔══╝██╔════╝██╔════╝
#        ██║███████╗       ██║   █████╗  ██╔████╔██║██████╔╝██║     ███████║   ██║   █████╗  ███████╗
#   ██   ██║╚════██║       ██║   ██╔══╝  ██║╚██╔╝██║██╔═══╝ ██║     ██╔══██║   ██║   ██╔══╝  ╚════██║
#   ╚█████╔╝███████║       ██║   ███████╗██║ ╚═╝ ██║██║     ███████╗██║  ██║   ██║   ███████╗███████║
#    ╚════╝ ╚══════╝       ╚═╝   ╚══════╝╚═╝     ╚═╝╚═╝     ╚══════╝╚═╝  ╚═╝   ╚═╝   ╚══════╝╚══════╝
?>
<div id="templatePopupConfig" style="display: none">
  <dl>
    <dt>Config Type:</dt>
    <dd>
      <select name="Type" onchange="toggleMode(this);">
        <option value="Path">Path</option>
        <option value="Port">Port</option>
        <option value="Variable">Variable</option>
        <option value="Device">Device</option>
      </select>
    </dd>
    <dt>Name:</dt>
    <dd><input type="text" name="Name" class="textPath"></dd>
    <div id="Target">
      <dt>Target:</dt>
      <dd><input type="text" name="Target" class="textPath"></dd>
    </div>
    <div id="Value">
      <dt>Value:</dt>
      <dd><input type="text" name="Value" class="textPath"></dd>
    </div>
    <div id="Default" class="advanced">
      <dt>Default Value:</dt>
      <dd><input type="text" name="Default" class="textPath"></dd>
    </div>
    <div id="Mode"></div>
    <dt>Description:</dt>
    <dd>
      <textarea name="Description" rows="6" style="width: 304px;"></textarea>
    </dd>
    <div class="advanced">
      <dt>Display:</dt>
      <dd>
        <select name="Display">
          <option value="always" selected>Always</option>
          <option value="advanced">Advanced</option>
          <option value="hidden">Hidden</option>
        </select>
      </dd>
      <dt>Required:</dt>
      <dd>
        <select name="Required">
          <option value="false" selected>No</option>
          <option value="true" >Yes</option>
        </select>
      </dd>
      <div id="Mask">
        <dt>Mask for Password:</dt>
        <dd>
          <select name="Mask">
            <option value="false" selected>No</option>
            <option value="true">Yes</option>
          </select>
        </dd>
      </div>
    </div>

  </dl>
</div>

<div id="templateDisplayConfig" style="display: none;">
  <input type="hidden" name="confName[]" value="{0}">
  <input type="hidden" name="confTarget[]" value="{1}">
  <input type="hidden" name="confDefault[]" value="{2}">
  <input type="hidden" name="confMode[]" value="{3}">
  <input type="hidden" name="confDescription[]" value="{4}">
  <input type="hidden" name="confType[]" value="{5}">
  <input type="hidden" name="confDisplay[]" value="{6}">
  <input type="hidden" name="confRequired[]" value="{7}">
  <input type="hidden" name="confMask[]" value="{8}">
  <table class="Preferences">
    <tr>
      <td style="vertical-align: top; min-width: 150px; white-space: nowrap; padding-top: 17px;" class="{11}"><b>{0}: </b></td>
      <td style="width: 100%">
        <input type="text" class="textPath" name="confValue[]" default="{2}" value="{9}" autocomplete="off" {11} > <button type="button" onclick="resetField(this);">Default</button>
        {10}
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td style="padding-top: 0px;"><div style='color: #b94a48;line-height: 1.6em'>{4}</div></td>
    </tr>
<!--     <tr class='advanced'>
      <td style="padding-top: 0px;">
        <div style="color: #3B5998;">
          <b>Type: </b>{5}<span class="spacer"></span>
          <b>Display: </b>{6}<span class="spacer"></span>
          <b>Required: </b>{7}<span class="spacer"></span>
          <b>Password: </b>{8}<span class="spacer"></span>
          <b>Mode: </b>{3}<span class="spacer"></span>
        </div>
      </td>
    </tr> -->
  </table>
</div>


<script type="text/javascript">
  function reloadTriggers() {
    $(".advanced").toggle($(".advanced-switch:first").is(":checked")); 
    $(".hidden").toggle($(".hidden-switch:first").is(":checked"));
    $(".numbersOnly").keypress(function(e){if(e.which != 45 && e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)){return false;}});
  }
  $(function(){
    // Load container info on page load
    if (typeof Settings != 'undefined') {
      for (var key in Settings) {
        if (Settings.hasOwnProperty(key)) {
          var target = $('#canvas').find('*[name=cont'+key+']:first');
          if (target.length) {
            var value = Settings[key];
            if (target.attr("type") == 'checkbox') {
              target.prop('checked', (value == 'true') ? true : false );
            } else if ($(target).prop('nodeName') == 'DIV'){
              target.html(value);
            } else {
              target.val(value);
            }
          }
        }
      }

      // Remove empty description
      if (! Settings.Description.length) {
        $('#canvas').find('#Overview:first').hide();
      }

      // Load config info
      for (var i = 0; i < Settings.Config.length; i++) {
        confNum += 1;
        Opts             = Settings.Config[i];
        Opts.Description = (Opts.Description.length) ? Opts.Description : "Container "+Opts.Type+": "+Opts.Target;
        if (Opts.Required == "true") {
          Opts.Buttons     = "<span class='advanced'><button type='button' onclick='editConfigPopup("+confNum+")'> Edit</button> ";
          Opts.Buttons    += "<button type='button' onclick='removeConfig("+confNum+");'> Remove</button></span>";
        } else {
          Opts.Buttons     = "<button type='button' onclick='editConfigPopup("+confNum+")'> Edit</button> ";
          Opts.Buttons    += "<button type='button' onclick='removeConfig("+confNum+");'> Remove</button>";
        }
        Opts.Number      = confNum;
        newConf = makeConfig(Opts);
        $("#configLocation").append(newConf);
        reloadTriggers();
      }
    } else {
      $('#canvas').find('#Overview:first').hide();
    }

    // Add switchButton
    $('.switch-on-off').each(function(){var checked = $(this).is(":checked");$(this).switchButton({labels_placement: "right", checked:checked});});

  });
</script>
<?END:?>
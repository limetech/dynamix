<?PHP
/* Copyright 2012, Andrew Hamer-Adams, http://www.pixeleyes.co.nz.
 * Copyright 2014, Bergware International.
 * Copyright 2014, Lime Technology
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
$var = parse_ini_file('state/var.ini');
?>
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-fonts.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-white.css">

<script>
// server uptime & update period
var uptime = <?=strtok(exec("cat /proc/uptime"),' ')?>;

function add(value, label, last) {
  return parseInt(value)+' '+label+(parseInt(value)!=1?'s':'')+(!last?', ':'');
}
function two(value, last) {
  return (parseInt(value)>9?'':'0')+parseInt(value)+(!last?':':'');
}
function updateTime() {
  document.getElementById('uptime').innerHTML = add(uptime/86400,'day')+two(uptime/3600%24)+two(uptime/60%60)+two(uptime%60,true);
  uptime++;
  setTimeout(updateTime, 1000);
}
</script>

<body onLoad="updateTime()">
<div style="margin-top:20px;font-size:12px;line-height:30px;color:#303030;margin-left:40px;">
<div><span style="width:90px;display:inline-block"><strong>Model:</strong></span>
<?
echo empty($var['SYS_MODEL']) ? 'N/A' : "{$var['SYS_MODEL']}";
?>
</div>
<div><span style="width:90px;display:inline-block"><strong>System:</strong></span>
<?
exec("dmidecode -q -t 2|awk -F: '/Manufacturer:/ {print $2}; /Product Name:/ {print $2}'", $product);
echo "{$product[0]} - {$product[1]}";
?>
</div>
<div><span style="width:90px; display:inline-block"><strong>CPU:</strong></span>
<?
function write($number) {
  $words = array('zero','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen','twenty');
  return $number<=count($words) ? $words[$number] : $number;
}
exec("dmidecode -q -t 4|awk -F: '/Version:/ {print $2};/Current Speed:/ {print $2}'",$cpu);
$cpumodel = str_replace(array("Processor","(C)","(R)","(TM)"),array("","&#169;","&#174;","&#8482;"),$cpu[0]);
if (strpos($cpumodel,'@')===false):
  $cpuspeed = explode(' ',trim($cpu[1]));
  if ($cpuspeed[0]>=1000 && $cpuspeed[1]=='MHz'):
    $cpuspeed[0] /= 1000;
    $cpuspeed[1] = 'GHz';
  endif;
  echo "$cpumodel @ {$cpuspeed[0]} {$cpuspeed[1]}";
else:
  echo $cpumodel;
endif;
?>
</div>
<div><span style="width:90px; display:inline-block"><strong>Cache:</strong></span>
<?
exec("dmidecode -q -t 7|awk -F: '/Socket Designation:/ {print $2}; /Installed Size:/ {print $2}'",$cache);
$name = array();
$size = "";
for ($i=0; $i<count($cache); $i+=2):
  if ($cache[$i+1]!=' 0 kB' && !in_array($cache[$i],$name)):
    if ($size) $size .= ', ';
    $size .= $cache[$i+1];
    $name[] = $cache[$i];
  endif;
endfor;
echo $size;
?>
</div>
<div><span style="width:90px; display:inline-block"><strong>Memory:</strong></span>
<?
exec("dmidecode -q -t memory|awk '/Maximum Capacity:/{print $3,$4};/Size:/{total+=$2;unit=$3} END{print total,unit}'",$memory);
echo "{$memory[1]} (max. {$memory[0]})";
?>
</div>
<div><span style="width:90px; display:inline-block"><strong>Network:</strong></span>
<?
exec("ifconfig -s|awk '/^(bond|eth)/{print $1}'",$sPorts);
$i = 0;
foreach ($sPorts as $port):
  unset($info);
  if ($i++) echo "<br><span style='width:94px; display:inline-block'>&nbsp;</span>";
  if ($port=='bond0'):
    $mode = exec("awk '/^Bonding Mode/' /proc/net/bonding/$port|cut -d: -f2");
    echo "$port: {$mode}";
  else:
    exec("ethtool $port|awk '/Speed:|Duplex:/{print $2}'",$info);
    echo $info[0][0]!='U' ? "$port: {$info[0]} - {$info[1]} Duplex" : "$port: not connected";
  endif;
endforeach;
?>
</div>
<?if (file_exists("/proc/xen")):?>
  <div><span style="width:90px;display:inline-block"><strong>Xen Version:</strong></span>
<?exec("xl info", $output);
  foreach ($output as $line):
    list($key,$value) = array_map('trim', explode(":", $line, 2));
    $info[$key] = $value;
  endforeach;
  echo "{$info['xen_major']}.{$info['xen_minor']}".$info['xen_extra'];
?></div>
  <div><span style="width:90px;display:inline-block"><strong>Dom0 Kernel:</strong></span>
<?$kernel = exec("uname -srm");
  echo $kernel;
?></div>
<?else:?>
  <div><span style="width:90px;display:inline-block"><strong>Kernel:</strong></span>
<?$kernel = exec("uname -srm");
  echo $kernel;
?></div>
<?endif;?>
<div><span style="width:90px; display:inline-block"><strong>OpenSSL:</strong></span>
<?$openssl_ver = exec("openssl version|cut -d' ' -f2");
  echo $openssl_ver;
?></div>
<div><span style="width:94px; display:inline-block"><strong>Uptime:</strong></span><span id="uptime"></span></div>
</div>
<center>
<?if ($_GET['more']):?>
<a href="<?=$_GET['more']?>" class="button" target="_parent" style="margin-top:10px">More Info</a>
<?endif;?>
</center>
</body>

<?php
$file = '/usr/local/emhttp/plugins/dynamix.kvm.manager/vncconnect.vnc';

if(!file_exists($file))
{
   // File doesn't exist, output error
   die('file not found');
}
else
{
   if(isset($_GET) && array_key_exists("srv", $_GET) && preg_match("/^[A-Za-z0-9\.-][A-Za-z0-9\.-][A-Za-z0-9\.-]+$/i",$_GET["srv"]))
   {
      $rdp_file = file_get_contents($file);
      // Set headers
      header("Cache-Control: public");
      header("Content-Description: File Transfer");
      header("Content-Disposition: attachment; filename=" . $_GET["srv"] . ".vnc");
      header("Content-Type: application/x-vnc");
      header("Content-Transfer-Encoding: 8bit");

      echo str_ireplace("{SERVER_ADDRESS}", $_GET["srv"], $rdp_file);
   }
   else
      die("You must specify a valid server name");
}
?> 
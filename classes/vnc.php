<?php
 $ip = $vars['IPADDR'];
      header("Cache-Control: public"); 
      header("Content-Description: File Transfer"); 
      header("Content-Disposition: attachment; filename=".$ip.".vnc"); 
      header("Content-Type: text/plain"); 
      header("Content-Transfer-Encoding: 8bit"); 

      echo "[connection]"."\n"."host=$ip"."\n"."port={$_GET['port']}"."\n"; 
?>

